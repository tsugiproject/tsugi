<?php

namespace Tsugi\Util;

use \Tsugi\Util\U;

/**
 * Outbound HTTP using the PHP curl extension.
 *
 * GET and POST (and other body methods) require ext-curl. There is no
 * stream or socket fallback.
 */
class Net {

    public static $VERIFY_PEER = false;

    public static function getLastGETURL() {
        global $LastGETURL;
        return $LastGETURL;
    }
    public static function getLastGETImpl() {
        global $LastGETImpl;
        return $LastGETImpl;
    }
    public static function getLastHeadersSent() {
        global $LastHeadersSent;
        return $LastHeadersSent;
    }
    public static function getLastHttpResponse() {
        global $last_http_response;
        return $last_http_response;
    }
    public static function getLastCurlError() {
        global $LastCurlError;
        return $LastCurlError;
    }
    public static function getLastHeadersReceived() {
        global $LastHeadersReceived;
        return $LastHeadersReceived;
    }
    public static function getLastBODYURL() {
        global $LastBODYURL;
        return $LastBODYURL;
    }
    public static function getLastBODYMethod() {
        global $LastBODYMethod;
        return $LastBODYMethod;
    }
    public static function getLastBODYImpl() {
        global $LastBODYImpl;
        return $LastBODYImpl;
    }
    public static function getLastBODYContent() {
        global $LastBODYContent;
        return $LastBODYContent;
    }

    public static function getLastBODYDebug() {
        global $LastBODYContent;
        global $LastBODYImpl;
        global $LastBODYMethod;
        global $LastBODYURL;
        global $LastHeadersReceived;
        global $LastHeadersSent;
        global $last_http_response;
        global $LastCurlError;

        // Caller knows the body_sent
        $retval = array();
        $retval['code'] = $last_http_response;
        $retval['body_impl'] = $LastBODYImpl;
        $retval['headers_sent'] = $LastHeadersSent;
        $retval['headers_received'] = $LastHeadersReceived;
        return $retval;
    }


    /**
     * Extract a set of header lines into an array
     *
     * Takes a newline separated header string and returns a key/value array.
     * Keys are kept as received (HTTP/2 and HTTP/3 send them lowercase).
     * Header names are case-insensitive (RFC 7230 / RFC 9110), so look
     * them up with U::getIgnoreCase() rather than U::get().
     */
    public static function parseHeaders($headerstr=false) {
        if ( $headerstr === false ) $headerstr = self::getLastHeadersReceived();
        $lines = explode("\n",$headerstr);
        $headermap = array();

        foreach ($lines as $line) {
            $pos = strpos($line, ':');
            if ( $pos < 1 ) continue;
            $key = substr($line,0,$pos);
            $value = trim(substr($line, $pos+1));
            if ( empty($key) || empty($value) ) continue;
            $headermap[$key] = $value;
        }
        return $headermap;
    }

    public static function doGet($url, $header = false) {
        global $LastGETURL;
        global $LastGETImpl;
        global $LastHeadersSent;
        global $last_http_response;
        global $LastCurlError;
        global $LastHeadersReceived;

        $LastGETURL = $url;
        $LastGETImpl = "CURL";
        $LastHeadersSent = false;
        $last_http_response = false;
        $LastCurlError = false;
        $LastHeadersReceived = false;

        return Net::getCurl($url, $header);
    }

    /**
     * Build the User-Agent string used for outbound HTTP requests.
     *
     * Canvas and other LMS platforms reject requests without a User-Agent
     * (PHP file_get_contents sends none / empty and gets HTTP 403).
     * Override via the CFG extension mechanism in your config.php file:
     *
     *     $CFG->setExtension('user_agent', 'MyTool/1.0 Tsugi/25.05');
     *
     * The default looks like this:
     *
     *     Tsugi/2025.12 (https://www.py4e.com/tsugi) PHP/8.4.1
     *
     * @return string
     */
    public static function getUserAgent() {
        global $CFG;

        $wwwroot = (isset($CFG) && is_object($CFG) && isset($CFG->wwwroot))
            ? $CFG->wwwroot : 'https://www.tsugi.org';

        $default_agent = 'Tsugi/' .
            (defined('TSUGI_VERSION') ? TSUGI_VERSION : 'dev') .
            ' (' . $wwwroot . ')' .
            ' PHP/' . phpversion();

        if ( isset($CFG) && is_object($CFG) && method_exists($CFG, 'getExtension') ) {
            return $CFG->getExtension('user_agent', $default_agent);
        }
        return $default_agent;
    }

    /**
     * Ensure a header string includes a User-Agent line.
     *
     * @param string|false $header Existing headers (newline-separated) or false
     * @return string Headers including User-Agent
     */
    public static function ensureUserAgentHeader($header=false) {
        $headers = is_string($header) ? trim($header) : '';
        if ( stripos($headers, 'User-Agent:') !== false ) {
            return $headers;
        }
        $ua = 'User-Agent: ' . self::getUserAgent();
        return U::strlen($headers) > 0 ? $headers . "\r\n" . $ua : $ua;
    }

    /**
     * Set the User-Agent header on a cURL handle.
     *
     * @param resource $ch A cURL handle
     * @return void
     */
    public static function setUserAgentCurl($ch) {
        curl_setopt($ch, CURLOPT_USERAGENT, self::getUserAgent());
    }


    private static function requireCurl() : void {
        if ( ! function_exists('curl_init') ) {
            throw new \Exception('The PHP curl extension is required for outbound HTTP');
        }
    }

    // Note - handles port numbers in URL automatically
    public static function getCurl($url, $header=false) {
      self::requireCurl();
      global $last_http_response;
      global $LastCurlError;
      global $LastHeadersSent;
      global $LastHeadersReceived;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);

      // Make sure that the header is an array and pitch white space
      $LastHeadersSent = trim($header);
      $header = explode("\n", trim($header));
      $htrim = Array();
      foreach ( $header as $h ) {
        $htrim[] = trim($h);
      }
      curl_setopt ($ch, CURLOPT_HTTPHEADER, $htrim);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned
      curl_setopt($ch, CURLOPT_HEADER, 1);

      // Thanks to more and more PHP's not shipping with CA's installed
      // This becomes necessary
      if ( self::$VERIFY_PEER ) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      }

      self::setUserAgentCurl($ch); // Set the User-Agent header

      // Send to remote and return data to caller.
      $result = curl_exec($ch);
      if ( $result === false ) {
              $LastCurlError = curl_error($ch);
      }
      $info = curl_getinfo($ch);
      $last_http_response = $info['http_code'];
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $LastHeadersReceived = substr($result, 0, $header_size);
      $body = substr($result, $header_size);
      if ( $body === false ) $body = "";
      return $body;
    }

    public static function getBodySentDebug() {
        global $LastBODYURL;
        global $LastBODYMethod;
        global $LastBODYImpl;
        global $LastHeadersSent;

        $ret = $LastBODYMethod . " Used: " . $LastBODYImpl . "\n" .
             $LastBODYURL . "\n\n" .
             $LastHeadersSent . "\n";
        return $ret;
    }

    public static function getBodyReceivedDebug() {
        global $LastBODYURL;
        global $LastBODYMethod;
        global $LastBODYImpl;
        global $LastHeadersReceived;
        global $last_http_response;
        global $LastCurlError;

        $ret = $LastBODYMethod . " Used: " . $LastBODYImpl . "\n" .
             $LastBODYURL . "\n" .
             (is_string($LastCurlError) ? "Curl Error: " . $LastCurlError . "\n" : ' ').
             "HTTP Response Code: " . $last_http_response . "\n" .
             $LastHeadersReceived . "\n";
        return $ret;
    }

    public static function getGetSentDebug() {
        global $LastGETImpl;
        global $LastGETURL;
        global $LastHeadersSent;

        $ret = "GET Used: " . $LastGETImpl . "\n" .
             $LastGETURL . "\n\n" .
             $LastHeadersSent . "\n";
        return $ret;
    }

    public static function getGetReceivedDebug() {
        global $LastGETURL;
        global $last_http_response;
        global $LastCurlError;
        global $LastGETImpl;
        global $LastHeadersReceived;

        $ret = "GET Used: " . $LastGETImpl . "\n" .
             $LastGETURL . "\n" .
             (is_string($LastCurlError) ? "Curl Error: " . $LastCurlError . "\n" : ' ').
             "HTTP Response: " . $last_http_response . "\n" .
             $LastHeadersReceived . "\n";
        return $ret;
    }

    public static function doBody($url, $method, $body, $header) {
        global $LastBODYURL;
        global $LastBODYMethod;
        global $LastBODYImpl;
        global $LastHeadersSent;
        global $last_http_response;
        global $LastCurlError;
        global $LastHeadersReceived;
        global $LastBODYContent;

        $LastBODYURL = $url;
        $LastBODYMethod = $method;
        $LastBODYImpl = "CURL";
        $LastHeadersSent = false;
        $last_http_response = false;
        $LastCurlError = false;
        $LastHeadersReceived = false;
        $LastBODYContent = false;

        $LastBODYContent = self::bodyCurl($url, $method, $body, $header);
        return $LastBODYContent;
    }

    // Note - handles port numbers in URL automatically
    public static function bodyCurl($url, $method, $body, $header) {
      self::requireCurl();
      global $last_http_response;
      global $LastCurlError;
      global $LastHeadersSent;
      global $LastHeadersReceived;
      global $LastBODYImpl;
      global $LastBODYMethod;
      global $LastBODYContent;

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $url);

      // Make sure that the header is an array and pitch white space
      $LastHeadersSent = trim($header);
      $header = explode("\n", trim($header));
      $htrim = Array();
      foreach ( $header as $h ) {
        $htrim[] = trim($h);
      }
      curl_setopt ($ch, CURLOPT_HTTPHEADER, $htrim);

      if ( $method == "POST" ) {
        curl_setopt($ch, CURLOPT_POST, 1);
      } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
      }

      curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

      curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // ask for results to be returned
      curl_setopt($ch, CURLOPT_HEADER, 1);

      // Thanks to more and more PHP's not shipping with CA's installed
      // This becomes necessary
      if ( static::$VERIFY_PEER ) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
      } else {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
      }

      self::setUserAgentCurl($ch); // Set the User-Agent header

      // Send to remote and return data to caller.
      $result = curl_exec($ch);
      if ( $result === false ) {
          $LastCurlError = curl_error($ch);
      }
      $info = curl_getinfo($ch);
      $last_http_response = $info['http_code'];
      if(curl_errno($ch))
      {
          error_log('Curl error: ' . curl_error($ch));
      }
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $LastHeadersReceived = substr($result, 0, $header_size);
      $body = substr($result, $header_size);
      if ( $body === false ) $body = ''; // Handle empty body
      $LastBODYContent = $body;
      $LastBODYImpl = "CURL";
      $LastBODYMethod = $method;
      return $body;
    }

    /**
     * Send a 403 header
     */
    public static function send403($msg=null, $detail=null) {
        if ( headers_sent() ) {
            echo("Headers sent - they would be:\n");
            echo("HTTP/1.1 403 Forbidden"."\n");
            if ( is_string($msg) ) echo("X-Error-Message: ".$msg."\n");
            if ( is_string($detail) ) echo("X-Error-Detail: ".$detail."\n");
        } else {
            header("HTTP/1.1 403 Forbidden");
            if ( is_string($msg) ) header("X-Error-Message: ".$msg);
            if ( is_string($detail) ) header("X-Error-Detail: ".$detail);
        }
    }

    /**
     * Send a 400 (Malformed request) header
     */
    public static function send400($msg='Malformed request', $detail=null) {
        if ( headers_sent() ) {
            echo("Headers sent - they would be:\n");
            echo("HTTP/1.1 400 ".$msg."\n");
            if ( is_string($detail) ) echo("X-Error-Detail: ".$detail."\n");
        } else {
            header("HTTP/1.1 400 ".$msg);
            if ( is_string($detail) ) header("X-Error-Detail: ".$detail);
        }
    }

    /**
     * Get the actual IP address of the incoming request.
     *
     * Handle being behind a load balancer or a proxy like Cloudflare.
     * This will often return NULL when talking to localhost to make sure
     * to test code using this on a real IP address.
     *
     * Adapted from: https://www.chriswiegman.com/2014/05/getting-correct-ip-address-php/
     * With some additional explode goodness via: http://stackoverflow.com/a/25193833/1994792
     *
     * @return string The IP address of the incoming request or NULL if it cannot be determined.
     */
    public static function getIP() {

        global $CFG;

        //Just get the headers if we can or else use the SERVER global
        if ( function_exists( 'apache_request_headers' ) ) {
            $rawheaders = apache_request_headers();
        } else {
            $rawheaders = $_SERVER;
        }

        // Lower case headers
        $headers = array();
        foreach($rawheaders as $key => $value) {
            $key = trim(strtolower($key));
            if ( !is_string($key) || empty($key) ) continue;
            $headers[$key] = $value;
        }

        // $filter_option = FILTER_FLAG_IPV4;
        // $filter_option = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
        $filter_option = 0;

        $the_ip = false;

        // When not behind proxy, trust IP from web server over headers
        if ( $CFG->trust_forwarded_ip === false && array_key_exists( 'REMOTE_ADDR', $_SERVER ) ) {
            $the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, $filter_option );
        }

        // Check Cloudflare headers
        if ( $the_ip === false && array_key_exists( 'http_cf_connecting_ip', $headers ) ) {
            $pieces = explode(',',$headers['http_cf_connecting_ip']);
            $the_ip = filter_var(current($pieces),FILTER_VALIDATE_IP, $filter_option );
        }

        if ( $the_ip === false && array_key_exists( 'cf-connecting-ip', $headers ) ) {
            $pieces = explode(',',$headers['cf-connecting-ip']);
            $the_ip = filter_var(current($pieces),FILTER_VALIDATE_IP, $filter_option );
        }

        // Get the forwarded IP from more traditional places
        if ( $the_ip == false && array_key_exists( 'x-forwarded-for', $headers ) ) {
            $pieces = explode(',',$headers['x-forwarded-for']);
            $the_ip = filter_var(current($pieces),FILTER_VALIDATE_IP, $filter_option );
        }

        if ( $the_ip === false && array_key_exists( 'http_x_forwarded_for', $headers ) ) {
            $pieces = explode(',',$headers['http_x_forwarded_for']);
            $the_ip = filter_var(current($pieces),FILTER_VALIDATE_IP, $filter_option );
        }

        if ( $the_ip === false && array_key_exists( 'remote_addr', $headers ) ) {
            $the_ip = filter_var( $headers['remote_addr'], FILTER_VALIDATE_IP, $filter_option );
        }

        // Fall through and get *something*
        if ( $the_ip === false && array_key_exists( 'REMOTE_ADDR', $_SERVER ) ) {
            $the_ip = filter_var( $_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, $filter_option );
        }

        if ( $the_ip === false ) $the_ip = NULL;
        return $the_ip;
    }

    /**
     * Return the IP Address of the current server
     */
    // https://stackoverflow.com/questions/3219178/php-how-to-get-local-ip-of-system
    public static function serverIP() {
        return getHostByName(getHostName());
    }

    /**
     * Return true if we have a routable address
     */
    // https://stackoverflow.com/questions/13818064/check-if-an-ip-address-is-private
    public static function isRoutable($ipaddr) {
        return $ipaddr == filter_var(
            $ipaddr,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE |  FILTER_FLAG_NO_RES_RANGE
        );
    }

    /**
     * Return true if the http code is 2xx (success)
     */
    public static function httpSuccess($httpcode) {
        return ($httpcode >= 200) && ($httpcode < 300);
    }

    /**
     * Put as much oomph into setting a cookie as we can
     *
     * @param string $name Cookie name
     * @param string $value Cookie value
     * @param string $domain Cookie domain
     * @param int $expires Unix timestamp for expiration
     * @param bool $partitioned Add Partitioned attribute (CHIPS) for iframe/third-party use
     */
    public static function setCookieStrong($name, $value, $domain, $expires, $partitioned = true) {
        $old_value = U::get($_COOKIE, $name);
        if (is_string($old_value) && U::strlen($old_value) > 1 ) {
            if ( $old_value == $value ) return;
        } else {
            if ( $partitioned && PHP_VERSION_ID >= 80500 ) {
                setcookie(
                    $name,
                    $value,
                    [
                        'expires' => $expires,
                        'path' => '/',
                        'domain' => $domain,
                        'samesite' => 'None',
                        'secure' => true,
                        'partitioned' => true,
                    ]
                );
            } elseif ( $partitioned && PHP_VERSION_ID < 80500 ) {
                $expires_str = gmdate('D, d M Y H:i:s \G\M\T', $expires);
                header(sprintf(
                    'Set-Cookie: %s=%s; expires=%s; path=/; domain=%s; secure; samesite=None; partitioned',
                    $name,
                    rawurlencode($value),
                    $expires_str,
                    $domain
                ));
            } else {
                setcookie(
                    $name,
                    $value,
                    [
                        'expires' => $expires,
                        'path' => '/',
                        'domain' => $domain,
                        'samesite' => 'None',
                        'secure' => true,
                    ]
                );
            }
        }
    }

}
