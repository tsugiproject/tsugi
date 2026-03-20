<?php

use \Tsugi\Util\U;
use \Tsugi\Util\PS;
use \Tsugi\Util\Net;

if ( ! defined('COOKIE_SESSION') ) define('COOKIE_SESSION', true);
require_once "../config.php";

function proxySmallJsonResolveIps($host) {
    $host = trim($host, '[]');
    if ( ! is_string($host) || U::strlen($host) < 1 ) {
        return array();
    }

    if ( filter_var($host, FILTER_VALIDATE_IP) ) {
        return array($host);
    }

    $ips = array();
    if ( function_exists('dns_get_record') ) {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA);
        if ( is_array($records) ) {
            foreach($records as $record) {
                $ip = U::get($record, 'ip');
                if ( ! $ip ) {
                    $ip = U::get($record, 'ipv6');
                }
                if ( filter_var($ip, FILTER_VALIDATE_IP) ) {
                    $ips[] = $ip;
                }
            }
        }
    }

    if ( count($ips) < 1 && function_exists('gethostbynamel') ) {
        $resolved = @gethostbynamel($host);
        if ( is_array($resolved) ) {
            foreach($resolved as $ip) {
                if ( filter_var($ip, FILTER_VALIDATE_IP) ) {
                    $ips[] = $ip;
                }
            }
        }
    }

    return array_values(array_unique($ips));
}

function proxySmallJsonValidateUrl($proxyUrl) {
    $parts = parse_url($proxyUrl);
    if ( ! is_array($parts) ) {
        return 'Invalid URL';
    }

    $host = U::get($parts, 'host');
    if ( ! is_string($host) || U::strlen($host) < 1 ) {
        return 'URL must include a host';
    }

    $ips = proxySmallJsonResolveIps($host);
    if ( count($ips) < 1 ) {
        return 'Unable to resolve host';
    }

    foreach($ips as $ip) {
        if ( ! Net::isRoutable($ip) ) {
            return 'Target host resolves to a private or reserved IP address';
        }
    }

    return true;
}

session_start();
if ( ! isset($_SESSION["admin"]) ) {
    die('Must be admin');
}

$proxyUrl = U::get($_GET, 'proxyUrl');
if ( ! $proxyUrl ) {
    die('Missing URL parameter');
}

$proxyUrl = new PS($proxyUrl);
if ( ! ( $proxyUrl->startswith('https://') || $proxyUrl->startswith('http://') ) ) {
    die('Urls must start with http or https');
}

$validation = proxySmallJsonValidateUrl((string) $proxyUrl);
if ( $validation !== true ) {
    die($validation);
}

// file_get_contents ( string $filename [, bool $use_include_path = FALSE [, resource $context [, int $offset = 0 [, int $maxlen ]]]] ) : string
// Limit the length to 10000 characters

$context = stream_context_create(array(
    'http' => array(
        'follow_location' => 0,
        'timeout' => 10,
    ),
));

$content = file_get_contents($proxyUrl, FALSE, $context, 0, 10000);
$json = json_decode($content);

if ( ! is_object($json) ) {
    die('JSON syntax error');
}

header('Content-Type: application/json');
echo(json_encode($json,  JSON_PRETTY_PRINT));
