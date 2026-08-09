<?php

namespace Tsugi\Services\Mail;

use \Tsugi\Util\U;
use \Tsugi\UI\Output;
use AsyncAws\Ses\SesClient;
use AsyncAws\Ses\Input\SendEmailRequest;
use AsyncAws\Ses\ValueObject\Body;
use AsyncAws\Ses\ValueObject\Content;
use AsyncAws\Ses\ValueObject\Destination;
use AsyncAws\Ses\ValueObject\EmailContent;
use AsyncAws\Ses\ValueObject\Message;
use AsyncAws\Ses\ValueObject\MessageHeader;
use AsyncAws\Ses\ValueObject\MessageTag;

/**
 * Outbound mail: Amazon SES when configured, otherwise PHP mail().
 * Honors mail_suppress and user/profile subscribe=-1.
 *
 * Prefer sendTransactional() / sendBulk() so SES can treat streams differently.
 */
class MailService {

    public const TYPE_TRANSACTIONAL = 'transactional';
    public const TYPE_BULK = 'bulk';

    /**
     * Whether SES is configured for outbound mail.
     */
    public static function isSesConfigured(): bool {
        global $CFG;
        if ( !isset($CFG->ses_region) || $CFG->ses_region === false ) {
            return false;
        }
        $region = trim((string) $CFG->ses_region);
        if ( $region === '' ) {
            return false;
        }
        return class_exists(SesClient::class);
    }

    /**
     * Active transport name when mail is enabled.
     *
     * @return 'ses'|'php'
     */
    public static function transport(): string {
        return self::isSesConfigured() ? 'ses' : 'php';
    }

    public static function computeCheck($identity) {
        global $CFG;
        return sha1($CFG->mailsecret . '::' . $identity);
    }

    public static function normalizeEmail($email): string {
        $email = trim((string) $email);
        if ( preg_match('/<([^>]+)>/', $email, $m) ) {
            $email = trim($m[1]);
        }
        return strtolower($email);
    }

    /**
     * Whether the mail_suppress table exists.
     */
    public static function suppressTableExists(): bool {
        global $CFG, $PDOX;
        if ( !isset($PDOX) || $PDOX === false || $PDOX === null ) {
            return false;
        }
        $fields = $PDOX->metadata($CFG->dbprefix . 'mail_suppress');
        return $fields !== false;
    }

    public static function isSuppressed($email): bool {
        global $CFG, $PDOX;
        $email = self::normalizeEmail($email);
        if ( $email === '' || strpos($email, '@') === false ) {
            return false;
        }
        if ( !self::suppressTableExists() ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT suppress_id FROM {$CFG->dbprefix}mail_suppress WHERE email = :E LIMIT 1",
            array(':E' => $email)
        );
        return $row !== false && $row !== null;
    }

    /**
     * Upsert an address into the suppress list.
     */
    public static function suppress($email, $reason, $detail=null, $message_id=null): void {
        global $CFG, $PDOX;
        $email = self::normalizeEmail($email);
        if ( $email === '' || strpos($email, '@') === false ) {
            return;
        }
        if ( !self::suppressTableExists() ) {
            error_log("MailService::suppress: mail_suppress table missing for $email ($reason)");
            return;
        }
        $reason = substr(preg_replace('/[^a-z_]/', '', strtolower((string) $reason)), 0, 32);
        if ( $reason === '' ) {
            $reason = 'unknown';
        }
        $detail = $detail !== null ? substr((string) $detail, 0, 255) : null;
        $message_id = $message_id !== null ? substr((string) $message_id, 0, 255) : null;

        $sql = "INSERT INTO {$CFG->dbprefix}mail_suppress
            (email, reason, detail, message_id, created_at, updated_at)
            VALUES (:E, :R, :D, :M, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                reason = VALUES(reason),
                detail = VALUES(detail),
                message_id = COALESCE(VALUES(message_id), message_id),
                updated_at = NOW()";
        $q = $PDOX->queryReturnError($sql, array(
            ':E' => $email,
            ':R' => $reason,
            ':D' => $detail,
            ':M' => $message_id,
        ));
        if ( !$q->success ) {
            error_log('MailService::suppress failed: ' . $q->errorImplode);
            return;
        }
        error_log("Mail suppressed: $email reason=$reason");
        self::optOutSubscribeByEmail($email);
    }

    /**
     * Set subscribe=-1 on profile and lti_user rows matching this email.
     */
    public static function optOutSubscribeByEmail($email): void {
        global $CFG, $PDOX;
        $email = self::normalizeEmail($email);
        if ( $email === '' || strpos($email, '@') === false ) {
            return;
        }
        if ( !isset($PDOX) || $PDOX === false || $PDOX === null ) {
            return;
        }

        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}profile
                SET subscribe = -1, updated_at = NOW()
                WHERE LOWER(email) = :E
                  AND (subscribe IS NULL OR subscribe <> -1)",
            array(':E' => $email)
        );
        if ( !$q->success ) {
            error_log('MailService::optOutSubscribeByEmail profile failed: ' . $q->errorImplode);
        } else if ( $q->rowCount() > 0 ) {
            error_log("Mail opt-out profile subscribe=-1 for $email rows=" . $q->rowCount());
        }

        $q = $PDOX->queryReturnError(
            "UPDATE {$CFG->dbprefix}lti_user
                SET subscribe = -1, updated_at = NOW()
                WHERE LOWER(email) = :E
                  AND (subscribe IS NULL OR subscribe <> -1)",
            array(':E' => $email)
        );
        if ( !$q->success ) {
            error_log('MailService::optOutSubscribeByEmail lti_user failed: ' . $q->errorImplode);
        } else if ( $q->rowCount() > 0 ) {
            error_log("Mail opt-out lti_user subscribe=-1 for $email rows=" . $q->rowCount());
        }
    }

    /**
     * Whether the mail_ses_events table exists.
     */
    public static function sesEventsTableExists(): bool {
        global $CFG, $PDOX;
        if ( !isset($PDOX) || $PDOX === false || $PDOX === null ) {
            return false;
        }
        $fields = $PDOX->metadata($CFG->dbprefix . 'mail_ses_events');
        return $fields !== false;
    }

    /**
     * Append a row to mail_ses_events (audit of SES notification + action taken).
     *
     * @param array{
     *   sns_message_id?: ?string,
     *   ses_message_id?: ?string,
     *   event_type: string,
     *   event_subtype?: ?string,
     *   email?: ?string,
     *   mail_type?: ?string,
     *   action: string,
     *   detail?: ?string,
     *   payload_json?: ?string
     * } $fields
     * @return bool True when inserted
     */
    public static function recordSesEvent(array $fields): bool {
        global $CFG, $PDOX;
        if ( !self::sesEventsTableExists() ) {
            error_log('MailService::recordSesEvent: mail_ses_events table missing');
            return false;
        }

        $event_type = substr(preg_replace('/[^a-z_]/', '', strtolower((string) U::get($fields, 'event_type', 'other'))), 0, 32);
        if ( $event_type === '' ) {
            $event_type = 'other';
        }
        $action = substr(preg_replace('/[^a-z_]/', '', strtolower((string) U::get($fields, 'action', 'ignore'))), 0, 32);
        if ( $action === '' ) {
            $action = 'ignore';
        }

        $email = U::get($fields, 'email', null);
        if ( is_string($email) && $email !== '' ) {
            $email = self::normalizeEmail($email);
            if ( $email === '' ) {
                $email = null;
            }
        } else {
            $email = null;
        }

        $sns_message_id = U::get($fields, 'sns_message_id', null);
        $ses_message_id = U::get($fields, 'ses_message_id', null);
        $event_subtype = U::get($fields, 'event_subtype', null);
        $mail_type = U::get($fields, 'mail_type', null);
        $detail = U::get($fields, 'detail', null);
        $payload_json = U::get($fields, 'payload_json', null);

        if ( is_string($sns_message_id) ) {
            $sns_message_id = substr($sns_message_id, 0, 255);
        } else {
            $sns_message_id = null;
        }
        if ( is_string($ses_message_id) ) {
            $ses_message_id = substr($ses_message_id, 0, 255);
        } else {
            $ses_message_id = null;
        }
        if ( is_string($event_subtype) ) {
            $event_subtype = substr($event_subtype, 0, 64);
        } else {
            $event_subtype = null;
        }
        if ( is_string($mail_type) ) {
            $mail_type = substr(preg_replace('/[^a-z_]/', '', strtolower($mail_type)), 0, 32);
            if ( $mail_type === '' ) {
                $mail_type = null;
            }
        } else {
            $mail_type = null;
        }
        if ( is_string($detail) ) {
            $detail = substr($detail, 0, 255);
        } else {
            $detail = null;
        }
        if ( !is_string($payload_json) || $payload_json === '' ) {
            $payload_json = null;
        }

        $sql = "INSERT INTO {$CFG->dbprefix}mail_ses_events
            (sns_message_id, ses_message_id, event_type, event_subtype, email, mail_type, action, detail, payload_json, created_at)
            VALUES (:SNS, :SES, :ETYPE, :ESUB, :EMAIL, :MTYPE, :ACT, :DET, :PAY, NOW())";
        $q = $PDOX->queryReturnError($sql, array(
            ':SNS' => $sns_message_id,
            ':SES' => $ses_message_id,
            ':ETYPE' => $event_type,
            ':ESUB' => $event_subtype,
            ':EMAIL' => $email,
            ':MTYPE' => $mail_type,
            ':ACT' => $action,
            ':DET' => $detail,
            ':PAY' => $payload_json,
        ));
        if ( !$q->success ) {
            error_log('MailService::recordSesEvent failed: ' . $q->errorImplode);
            return false;
        }
        return true;
    }

    /**
     * True when user or linked profile has subscribe = -1 (no mail).
     */
    public static function isUserOptedOut($user_id): bool {
        global $CFG, $PDOX;
        $user_id = (int) $user_id;
        if ( $user_id <= 0 || !isset($PDOX) || $PDOX === false || $PDOX === null ) {
            return false;
        }
        $row = $PDOX->rowDie(
            "SELECT U.subscribe AS user_subscribe, P.subscribe AS profile_subscribe
                FROM {$CFG->dbprefix}lti_user U
                LEFT JOIN {$CFG->dbprefix}profile P ON P.profile_id = U.profile_id
                WHERE U.user_id = :UID",
            array(':UID' => $user_id)
        );
        if ( $row === false || $row === null ) {
            return false;
        }
        if ( isset($row['user_subscribe']) && (int) $row['user_subscribe'] === -1 ) {
            return true;
        }
        if ( isset($row['profile_subscribe']) && (int) $row['profile_subscribe'] === -1 ) {
            return true;
        }
        return false;
    }

    /**
     * One-to-one / system response mail (key approval, notices, etc.).
     * Returns true on success, false on failure, null when mail is disabled.
     */
    public static function sendTransactional($to, $subject, $message, $id=false, $token=false) {
        return self::sendResult(self::sendDetailedTransactional($to, $subject, $message, $id, $token));
    }

    /**
     * Campaign / list-style mail to many recipients.
     * Returns true on success, false on failure, null when mail is disabled.
     */
    public static function sendBulk($to, $subject, $message, $id=false, $token=false) {
        return self::sendResult(self::sendDetailedBulk($to, $subject, $message, $id, $token));
    }

    /**
     * @deprecated Prefer sendTransactional() or sendBulk(). Defaults to transactional.
     */
    public static function send($to, $subject, $message, $id=false, $token=false) {
        return self::sendTransactional($to, $subject, $message, $id, $token);
    }

    public static function sendDetailedTransactional($to, $subject, $message, $id=false, $token=false): array {
        return self::sendDetailedWithType(self::TYPE_TRANSACTIONAL, $to, $subject, $message, $id, $token);
    }

    public static function sendDetailedBulk($to, $subject, $message, $id=false, $token=false): array {
        return self::sendDetailedWithType(self::TYPE_BULK, $to, $subject, $message, $id, $token);
    }

    /**
     * @deprecated Prefer sendDetailedTransactional() or sendDetailedBulk(). Defaults to transactional.
     */
    public static function sendDetailed($to, $subject, $message, $id=false, $token=false): array {
        return self::sendDetailedTransactional($to, $subject, $message, $id, $token);
    }

    /**
     * @param array{success: bool, disabled: bool} $detail
     * @return bool|null
     */
    private static function sendResult(array $detail) {
        if ( $detail['disabled'] ) {
            return null;
        }
        return $detail['success'];
    }

    /**
     * @return array{success: bool, transport: string, error: ?string, message_id: ?string, disabled: bool, suppressed: bool, type: string}
     */
    private static function sendDetailedWithType(string $type, $to, $subject, $message, $id=false, $token=false): array {
        global $CFG;

        if ( $type !== self::TYPE_BULK ) {
            $type = self::TYPE_TRANSACTIONAL;
        }

        $result = array(
            'success' => false,
            'transport' => self::transport(),
            'error' => null,
            'message_id' => null,
            'disabled' => false,
            'suppressed' => false,
            'type' => $type,
        );

        if ( (!isset($CFG->maildomain)) || $CFG->maildomain === false ) {
            $result['disabled'] = true;
            $result['error'] = 'Mail disabled: $CFG->maildomain is not set';
            return $result;
        }

        if ( !(isset($CFG->maileol) && isset($CFG->wwwroot) && isset($CFG->maildomain)) ) {
            die_with_error_log("Incomplete mail configuration in mailSend");
        }

        if ( empty($to) || empty($subject) ) {
            $result['error'] = 'Missing to or subject';
            return $result;
        }

        if ( self::isSuppressed($to) ) {
            $result['suppressed'] = true;
            $result['error'] = 'suppressed';
            error_log("Mail suppressed (list): $to $subject");
            return $result;
        }

        if ( U::strlen($id) > 0 && self::isUserOptedOut($id) ) {
            $result['suppressed'] = true;
            $result['error'] = 'suppressed';
            error_log("Mail suppressed (subscribe=-1): user_id=$id $to $subject");
            return $result;
        }

        $msg = $message;
        if ( substr($msg, -1) !== "\n" ) {
            $msg .= "\n";
        }

        $unsubscribe_url = self::unsubscribeUrl($id, $token);
        $from = self::fromAddress();

        error_log("Mail to: $to $subject via " . $result['transport'] . " type=$type");

        if ( self::isSesConfigured() ) {
            return self::sendViaSes($to, $subject, $msg, $from, $unsubscribe_url, $type, $result);
        }

        return self::sendViaPhpMail($to, $subject, $msg, $from, $unsubscribe_url, $id, $token, $type, $result);
    }

    private static function fromAddress(): string {
        global $CFG;
        if ( isset($CFG->ses_from) && $CFG->ses_from !== false ) {
            $from = trim((string) $CFG->ses_from);
            if ( $from !== '' ) {
                return $from;
            }
        }
        return 'no-reply@' . $CFG->maildomain;
    }

    private static function unsubscribeUrl($id, $token): string {
        global $CFG;
        $manage = $CFG->wwwroot . "/profile";
        if ( U::strlen($id) > 0 && U::strlen($token) > 0 ) {
            return Output::getUtilUrl("/unsubscribe?id=$id&token=$token");
        }
        return $manage;
    }

    /**
     * Configuration set for this mail type (bulk can use a separate set).
     */
    private static function configurationSetForType(string $type): ?string {
        global $CFG;
        if ( $type === self::TYPE_BULK
            && isset($CFG->ses_configuration_set_bulk) && $CFG->ses_configuration_set_bulk !== false ) {
            $bulk = trim((string) $CFG->ses_configuration_set_bulk);
            if ( $bulk !== '' ) {
                return $bulk;
            }
        }
        if ( isset($CFG->ses_configuration_set) && $CFG->ses_configuration_set !== false ) {
            $cfgset = trim((string) $CFG->ses_configuration_set);
            if ( $cfgset !== '' ) {
                return $cfgset;
            }
        }
        return null;
    }

    private static function sendViaPhpMail($to, $subject, $msg, $from, $unsubscribe_url, $id, $token, string $type, array $result): array {
        global $CFG;

        $EOL = $CFG->maileol;
        $maildomain = $CFG->maildomain;
        $headers = "From: $from" . $EOL .
            "Return-Path: <bounced-$id-$token@$maildomain>" . $EOL .
            "List-Unsubscribe: <$unsubscribe_url>" . $EOL .
            "X-Tsugi-Mail-Type: $type" . $EOL .
            'X-Mailer: PHP/' . phpversion();

        $ok = mail($to, $subject, $msg, $headers);
        $result['success'] = (bool) $ok;
        if ( !$ok ) {
            $result['error'] = 'PHP mail() returned false';
        }
        return $result;
    }

    private static function sendViaSes($to, $subject, $msg, $from, $unsubscribe_url, string $type, array $result): array {
        global $CFG;

        $config = array(
            'region' => trim((string) $CFG->ses_region),
        );
        if ( isset($CFG->ses_key) && $CFG->ses_key !== false && trim((string) $CFG->ses_key) !== ''
            && isset($CFG->ses_secret) && $CFG->ses_secret !== false && trim((string) $CFG->ses_secret) !== '' ) {
            $config['accessKeyId'] = trim((string) $CFG->ses_key);
            $config['accessKeySecret'] = trim((string) $CFG->ses_secret);
        }

        // Do not set Return-Path here — SES treats it as a reserved header.
        $headers = array(
            array('Name' => 'List-Unsubscribe', 'Value' => '<' . $unsubscribe_url . '>'),
            array('Name' => 'X-Tsugi-Mail-Type', 'Value' => $type),
            array('Name' => 'X-Mailer', 'Value' => 'Tsugi/SES'),
        );

        $request = array(
            'FromEmailAddress' => $from,
            'Destination' => new Destination(array(
                'ToAddresses' => array($to),
            )),
            'Content' => new EmailContent(array(
                'Simple' => new Message(array(
                    'Subject' => new Content(array('Data' => $subject, 'Charset' => 'UTF-8')),
                    'Body' => new Body(array(
                        'Text' => new Content(array('Data' => $msg, 'Charset' => 'UTF-8')),
                    )),
                    'Headers' => array_map(static function ($h) {
                        return new MessageHeader($h);
                    }, $headers),
                )),
            )),
            'EmailTags' => array(
                new MessageTag(array('Name' => 'mail_type', 'Value' => $type)),
            ),
        );

        $cfgset = self::configurationSetForType($type);
        if ( $cfgset !== null ) {
            $request['ConfigurationSetName'] = $cfgset;
        }

        try {
            $ses = new SesClient($config);
            $response = $ses->sendEmail(new SendEmailRequest($request));
            $message_id = $response->getMessageId();
            $result['success'] = true;
            $result['message_id'] = $message_id !== null && $message_id !== '' ? $message_id : null;
            return $result;
        } catch ( \Throwable $e ) {
            error_log('SES mail failed: ' . $e->getMessage());
            $result['error'] = $e->getMessage();
            return $result;
        }
    }
}
