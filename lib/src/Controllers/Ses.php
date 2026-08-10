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
     * Verify an SNS message signature (matches aws-php-sns-message-validator).
     * @see https://docs.aws.amazon.com/sns/latest/dg/sns-verify-signature-of-message.html
     * @see https://github.com/aws/aws-php-sns-message-validator
     */
    private static function verifySnsSignature(array $envelope): bool {
        // Lambda-style key casing → canonical SNS keys
        if ( isset($envelope['SigningCertUrl']) && !isset($envelope['SigningCertURL']) ) {
            $envelope['SigningCertURL'] = $envelope['SigningCertUrl'];
        }
        if ( isset($envelope['SubscribeUrl']) && !isset($envelope['SubscribeURL']) ) {
            $envelope['SubscribeURL'] = $envelope['SubscribeUrl'];
        }

        $cert_url = U::get($envelope, 'SigningCertURL', '');
        $signature = U::get($envelope, 'Signature', '');
        $sig_version = (string) U::get($envelope, 'SignatureVersion', '1');

        if ( !is_string($cert_url) || !is_string($signature) || $cert_url === '' || $signature === '' ) {
            error_log('SES SNS verify: missing SigningCertURL or Signature');
            return false;
        }
        if ( !self::isAllowedSigningCertUrl($cert_url) ) {
            error_log('SES SNS verify: disallowed SigningCertURL host: ' . $cert_url);
            return false;
        }
        if ( $sig_version !== '1' && $sig_version !== '2' ) {
            error_log('SES SNS verify: unsupported SignatureVersion=' . $sig_version);
            return false;
        }

        // Alphabetical signable keys; include only those present (AWS validator style).
        $signable_keys = array(
            'Message',
            'MessageId',
            'Subject',
            'SubscribeURL',
            'Timestamp',
            'Token',
            'TopicArn',
            'Type',
        );
        $string_to_sign = '';
        foreach ( $signable_keys as $key ) {
            if ( isset($envelope[$key]) ) {
                $string_to_sign .= $key . "\n" . $envelope[$key] . "\n";
            }
        }
        if ( $string_to_sign === '' ) {
            error_log('SES SNS verify: empty string-to-sign');
            return false;
        }

        $cert_pem = self::fetchSigningCert($cert_url);
        if ( !is_string($cert_pem) || $cert_pem === '' ) {
            error_log('SES SNS verify: failed to fetch SigningCertURL: ' . $cert_url);
            return false;
        }
        $pub_key = openssl_get_publickey($cert_pem);
        if ( $pub_key === false ) {
            error_log('SES SNS verify: openssl_get_publickey failed');
            return false;
        }

        $decoded = base64_decode($signature, true);
        if ( $decoded === false ) {
            error_log('SES SNS verify: Signature base64 decode failed');
            return false;
        }

        $algo = ( $sig_version === '2' ) ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $ok = openssl_verify($string_to_sign, $decoded, $pub_key, $algo);
        if ( $ok !== 1 ) {
            error_log('SES SNS verify: openssl_verify failed (result=' . var_export($ok, true) . ')');
            return false;
        }
        return true;
    }

    private static function fetchSigningCert(string $url): ?string {
        $cert = @file_get_contents($url);
        if ( is_string($cert) && $cert !== '' ) {
            return $cert;
        }
        if ( function_exists('curl_init') ) {
            $ch = curl_init($url);
            curl_setopt_array($ch, array(
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTPS,
            ));
            $cert = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ( is_string($cert) && $cert !== '' && $code >= 200 && $code < 300 ) {
                return $cert;
            }
        }
        return null;
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
        // Must be an SNS cert URL ending in .pem (not arbitrary amazonaws hosts / S3).
        if ( !preg_match('/\.pem$/i', $url) ) {
            return false;
        }
        // sns.<region>.amazonaws.com or sns.<region>.amazonaws.com.cn
        return (bool) preg_match('/^sns\.[a-z0-9-]{3,}\.amazonaws\.com(\.cn)?$/', $host);
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
