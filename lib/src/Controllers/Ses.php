<?php

namespace Tsugi\Controllers;

use Tsugi\Lumen\Controller;
use Tsugi\Lumen\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tsugi\Core\LTIX;
use Tsugi\Services\Mail\MailService;
use Tsugi\Util\U;

/**
 * Amazon SES → SNS webhook for bounce/complaint events.
 */
class Ses extends Controller {

    const ROUTE = '/ses';

    public static function routes(Application $app, $prefix = self::ROUTE) {
        $app->router->post($prefix.'/sns', function (Request $request) use ($app) {
            return Ses::postSns($app);
        });
        $app->router->post($prefix.'/sns/', function (Request $request) use ($app) {
            return Ses::postSns($app);
        });
    }

    /**
     * Handle SNS subscription confirmations and SES event notifications.
     */
    public static function postSns(Application $app) {
        LTIX::getConnection();

        $payload = @file_get_contents('php://input');
        if ( !is_string($payload) || $payload === '' ) {
            return self::textResponse('Empty body', 400);
        }

        $envelope = json_decode($payload, true);
        if ( !is_array($envelope) || !isset($envelope['Type']) ) {
            return self::textResponse('Invalid SNS JSON', 400);
        }

        if ( !self::verifySnsSignature($envelope) ) {
            error_log('SES SNS: signature verification failed');
            return self::textResponse('Invalid signature', 403);
        }

        $type = (string) $envelope['Type'];

        if ( $type === 'SubscriptionConfirmation' || $type === 'UnsubscribeConfirmation' ) {
            $url = U::get($envelope, 'SubscribeURL', '');
            if ( is_string($url) && $url !== '' && self::isAllowedSubscribeUrl($url) ) {
                $ok = @file_get_contents($url);
                error_log('SES SNS ' . $type . ' SubscribeURL fetch ' . ($ok !== false ? 'ok' : 'failed'));
            } else {
                error_log('SES SNS ' . $type . ' missing or disallowed SubscribeURL');
            }
            return self::textResponse('OK', 200);
        }

        if ( $type !== 'Notification' ) {
            error_log('SES SNS ignored Type=' . $type);
            return self::textResponse('OK', 200);
        }

        $message_raw = U::get($envelope, 'Message', '');
        if ( !is_string($message_raw) || $message_raw === '' ) {
            return self::textResponse('OK', 200);
        }

        $event = json_decode($message_raw, true);
        if ( !is_array($event) ) {
            error_log('SES SNS: Message is not JSON');
            return self::textResponse('OK', 200);
        }

        $sns_message_id = U::get($envelope, 'MessageId', null);
        self::processSesEvent($event, is_string($sns_message_id) ? $sns_message_id : null);
        return self::textResponse('OK', 200);
    }

    private static function processSesEvent(array $event, ?string $sns_message_id): void {
        $event_type = strtolower((string) U::get($event, 'eventType', U::get($event, 'notificationType', '')));
        if ( $event_type === '' ) {
            $event_type = 'other';
        }

        $mail = U::get($event, 'mail', array());
        if ( !is_array($mail) ) {
            $mail = array();
        }
        $ses_message_id = U::get($mail, 'messageId', null);
        if ( !is_string($ses_message_id) ) {
            $ses_message_id = null;
        }
        $mail_type = self::extractMailType($mail);
        $payload_json = self::trimPayloadJson($event);

        if ( $event_type === 'bounce' ) {
            $bounce = U::get($event, 'bounce', array());
            if ( !is_array($bounce) ) {
                self::recordOne($sns_message_id, $ses_message_id, 'bounce', null, null, $mail_type, 'error', 'missing bounce object', $payload_json);
                return;
            }
            $bounce_type = (string) U::get($bounce, 'bounceType', '');
            $bounce_sub = (string) U::get($bounce, 'bounceSubType', '');
            $subtype = trim($bounce_type . '/' . $bounce_sub, '/');
            $permanent = (strcasecmp($bounce_type, 'Permanent') === 0);
            $action = $permanent ? 'suppress' : 'ignore_soft_bounce';
            $recipients = U::get($bounce, 'bouncedRecipients', array());
            if ( !is_array($recipients) || count($recipients) < 1 ) {
                self::recordOne($sns_message_id, $ses_message_id, 'bounce', $subtype, null, $mail_type, $action, 'no recipients', $payload_json);
                return;
            }
            foreach ( $recipients as $recipient ) {
                if ( !is_array($recipient) ) {
                    continue;
                }
                $email = U::get($recipient, 'emailAddress', '');
                if ( !is_string($email) || $email === '' ) {
                    continue;
                }
                $detail = $subtype !== '' ? $subtype : null;
                if ( $permanent ) {
                    try {
                        MailService::suppress($email, 'bounce', $detail, $ses_message_id);
                        self::recordOne($sns_message_id, $ses_message_id, 'bounce', $subtype, $email, $mail_type, 'suppress', $detail, $payload_json);
                    } catch ( \Throwable $e ) {
                        error_log('SES bounce suppress failed: ' . $e->getMessage());
                        self::recordOne($sns_message_id, $ses_message_id, 'bounce', $subtype, $email, $mail_type, 'error', $e->getMessage(), $payload_json);
                    }
                } else {
                    error_log("SES SNS soft/transient bounce type=$bounce_type subtype=$bounce_sub email=$email");
                    self::recordOne($sns_message_id, $ses_message_id, 'bounce', $subtype, $email, $mail_type, 'ignore_soft_bounce', $detail, $payload_json);
                }
            }
            return;
        }

        if ( $event_type === 'complaint' ) {
            $complaint = U::get($event, 'complaint', array());
            if ( !is_array($complaint) ) {
                self::recordOne($sns_message_id, $ses_message_id, 'complaint', null, null, $mail_type, 'error', 'missing complaint object', $payload_json);
                return;
            }
            $feedback = (string) U::get($complaint, 'complaintFeedbackType', '');
            $recipients = U::get($complaint, 'complainedRecipients', array());
            if ( !is_array($recipients) || count($recipients) < 1 ) {
                self::recordOne($sns_message_id, $ses_message_id, 'complaint', $feedback !== '' ? $feedback : null, null, $mail_type, 'suppress', 'no recipients', $payload_json);
                return;
            }
            foreach ( $recipients as $recipient ) {
                if ( !is_array($recipient) ) {
                    continue;
                }
                $email = U::get($recipient, 'emailAddress', '');
                if ( !is_string($email) || $email === '' ) {
                    continue;
                }
                $detail = $feedback !== '' ? $feedback : null;
                try {
                    MailService::suppress($email, 'complaint', $detail, $ses_message_id);
                    self::recordOne($sns_message_id, $ses_message_id, 'complaint', $detail, $email, $mail_type, 'suppress', $detail, $payload_json);
                } catch ( \Throwable $e ) {
                    error_log('SES complaint suppress failed: ' . $e->getMessage());
                    self::recordOne($sns_message_id, $ses_message_id, 'complaint', $detail, $email, $mail_type, 'error', $e->getMessage(), $payload_json);
                }
            }
            return;
        }

        if ( $event_type === 'delivery' ) {
            $delivery = U::get($event, 'delivery', array());
            $recipients = is_array($delivery) ? U::get($delivery, 'recipients', array()) : array();
            if ( !is_array($recipients) || count($recipients) < 1 ) {
                self::recordOne($sns_message_id, $ses_message_id, 'delivery', null, null, $mail_type, 'ignore_delivery', null, $payload_json);
                return;
            }
            foreach ( $recipients as $email ) {
                if ( !is_string($email) || $email === '' ) {
                    continue;
                }
                self::recordOne($sns_message_id, $ses_message_id, 'delivery', null, $email, $mail_type, 'ignore_delivery', null, $payload_json);
            }
            return;
        }

        if ( $event_type === 'reject' ) {
            $reject = U::get($event, 'reject', array());
            $reason = is_array($reject) ? (string) U::get($reject, 'reason', '') : '';
            self::recordOne($sns_message_id, $ses_message_id, 'reject', $reason !== '' ? $reason : null, null, $mail_type, 'ignore', $reason !== '' ? $reason : null, $payload_json);
            return;
        }

        error_log('SES SNS ignored eventType=' . $event_type);
        self::recordOne($sns_message_id, $ses_message_id, $event_type !== '' ? $event_type : 'other', null, null, $mail_type, 'ignore', null, $payload_json);
    }

    private static function recordOne(
        ?string $sns_message_id,
        ?string $ses_message_id,
        string $event_type,
        ?string $event_subtype,
        ?string $email,
        ?string $mail_type,
        string $action,
        ?string $detail,
        ?string $payload_json
    ): void {
        MailService::recordSesEvent(array(
            'sns_message_id' => $sns_message_id,
            'ses_message_id' => $ses_message_id,
            'event_type' => $event_type,
            'event_subtype' => $event_subtype,
            'email' => $email,
            'mail_type' => $mail_type,
            'action' => $action,
            'detail' => $detail,
            'payload_json' => $payload_json,
        ));
    }

    private static function extractMailType(array $mail): ?string {
        $tags = U::get($mail, 'tags', null);
        if ( is_array($tags) ) {
            if ( isset($tags['mail_type']) ) {
                $v = $tags['mail_type'];
                if ( is_array($v) && isset($v[0]) && is_string($v[0]) ) {
                    return $v[0];
                }
                if ( is_string($v) ) {
                    return $v;
                }
            }
            foreach ( $tags as $tag ) {
                if ( !is_array($tag) ) {
                    continue;
                }
                $name = strtolower((string) U::get($tag, 'name', U::get($tag, 'Name', '')));
                if ( $name === 'mail_type' ) {
                    $val = U::get($tag, 'value', U::get($tag, 'Value', null));
                    if ( is_string($val) && $val !== '' ) {
                        return $val;
                    }
                }
            }
        }
        $headers = U::get($mail, 'headers', null);
        if ( is_array($headers) ) {
            foreach ( $headers as $header ) {
                if ( !is_array($header) ) {
                    continue;
                }
                $name = strtolower((string) U::get($header, 'name', ''));
                if ( $name === 'x-tsugi-mail-type' ) {
                    $val = U::get($header, 'value', null);
                    if ( is_string($val) && $val !== '' ) {
                        return $val;
                    }
                }
            }
        }
        return null;
    }

    private static function trimPayloadJson(array $event): ?string {
        $json = json_encode($event);
        if ( !is_string($json) ) {
            return null;
        }
        // Keep admin detail usable without storing huge blobs.
        if ( strlen($json) > 65000 ) {
            $json = substr($json, 0, 65000);
        }
        return $json;
    }

    /**
     * Verify an SNS message signature.
     * @see https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html
     */
    private static function verifySnsSignature(array $envelope): bool {
        $cert_url = U::get($envelope, 'SigningCertURL', U::get($envelope, 'SigningCertUrl', ''));
        $signature = U::get($envelope, 'Signature', '');
        $sig_version = (string) U::get($envelope, 'SignatureVersion', '1');

        if ( !is_string($cert_url) || !is_string($signature) || $cert_url === '' || $signature === '' ) {
            return false;
        }
        if ( !self::isAllowedSigningCertUrl($cert_url) ) {
            return false;
        }

        $type = (string) U::get($envelope, 'Type', '');
        if ( $type === 'Notification' ) {
            $fields = array('Message', 'MessageId', 'Subject', 'Timestamp', 'TopicArn', 'Type');
        } else {
            $fields = array('Message', 'MessageId', 'SubscribeURL', 'Timestamp', 'Token', 'TopicArn', 'Type');
        }

        $string_to_sign = '';
        foreach ( $fields as $field ) {
            if ( !array_key_exists($field, $envelope) ) {
                // Subject is optional on Notification
                if ( $field === 'Subject' ) {
                    continue;
                }
                // SubscribeURL key casing
                if ( $field === 'SubscribeURL' && array_key_exists('SubscribeUrl', $envelope) ) {
                    $string_to_sign .= $field . "\n" . $envelope['SubscribeUrl'] . "\n";
                    continue;
                }
                return false;
            }
            $string_to_sign .= $field . "\n" . $envelope[$field] . "\n";
        }

        $cert_pem = @file_get_contents($cert_url);
        if ( !is_string($cert_pem) || $cert_pem === '' ) {
            return false;
        }
        $pub_key = openssl_pkey_get_public($cert_pem);
        if ( $pub_key === false ) {
            return false;
        }

        $decoded = base64_decode($signature, true);
        if ( $decoded === false ) {
            return false;
        }

        $algo = ( $sig_version === '2' ) ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $ok = openssl_verify($string_to_sign, $decoded, $pub_key, $algo);
        return $ok === 1;
    }

    private static function isAllowedSigningCertUrl(string $url): bool {
        $parts = parse_url($url);
        if ( !is_array($parts) ) {
            return false;
        }
        if ( strtolower((string) U::get($parts, 'scheme', '')) !== 'https' ) {
            return false;
        }
        $host = strtolower((string) U::get($parts, 'host', ''));
        if ( $host === '' ) {
            return false;
        }
        // sns.<region>.amazonaws.com
        return (bool) preg_match('/^sns\.[a-z0-9-]+\.amazonaws\.com$/', $host);
    }

    private static function isAllowedSubscribeUrl(string $url): bool {
        $parts = parse_url($url);
        if ( !is_array($parts) ) {
            return false;
        }
        if ( strtolower((string) U::get($parts, 'scheme', '')) !== 'https' ) {
            return false;
        }
        $host = strtolower((string) U::get($parts, 'host', ''));
        return (bool) preg_match('/(^|\.)amazonaws\.com$/', $host);
    }

    private static function textResponse(string $body, int $status): Response {
        return new Response($body, $status, array('Content-Type' => 'text/plain; charset=UTF-8'));
    }
}
