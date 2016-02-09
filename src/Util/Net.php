<?php

namespace Tsugi\Util;

/**
 * This general purpose library for HTTP communications.
 *
 * This class attempts to solve the problem that lots of
 * PHP environments have different approaches to doing
 * GET and POST requests.  Some use CURL and others use
 * context streams.   This code tries one way or another
 * to figure out the best way to handle these and make
 * these calls work using whatever library code that
 * is available.
 *
 * The best way for things work is for CURL to be avaialable.
 * If CURL is available, it is used for everything and
 * forks well.
 *
 * @TODO We need to make this non-static and configure it
 * so it prefers or exclusively uses a particular transport.
 *
 */
class Net {

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

    /**
     * Extract a set of header lines into an array
     * 
     * Takes a newline separated header sting and returns a key/value array
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
            if ( strlen($key) < 1 || strlen($value) < 1 ) continue;
            $headermap[$key] = $value;
        }
        return $headermap;
    }

    public static function doGet($url, $header = false) {
        global $LastGETURL;
        global $LastGETImpl;
        global $LastHeadersSent;
        global $last_http_response;
        global $LastHeadersReceived;

        $LastGETURL = $url;
        $LastGETImpl = false;
        $LastHeadersSent = false;
        $last_http_response = false;
        $LastHeadersReceived = false;
        $lastGETResponse = false;

        $LastGETImpl = "CURL";
        $lastGETResponse = Net::getCurl($url, $header);
        if ( $lastGETResponse !== false ) return $lastGETResponse;
        $LastGETImpl = "Stream";
        $lastGETResponse = Net::getStream($url, $header);
        if ( $lastGETResponse !== false ) return $lastGETResponse;
        error_log("Unable to GET Url=$url");
        error_log("Header: $header");
        throw new \Exception("Unable to GET url=".$url);
    }

    public static function getStream($url, $header=false) {
        $params = array('http' => array(
            'method' => 'GET',
            'header' => $header
            ));

        $ctx = stream_context_create($params);
        try {
            $response = file_get_contents($url, false, $ctx);
        } catch (Exception $e) {
            return false;
        }
        return $response;
    }

    public static function getCurl($url, $header=false) {
      if ( ! function_exists('curl_init') ) return false;
      global $last_http_response;
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
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

      // Send to remote and return data to caller.
      $result = curl_exec($ch);
      $info = curl_getinfo($ch);
      $last_http_response = $info['http_code'];
      $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
      $LastHeadersReceived = substr($result, 0, $header_size);
      $body = substr($result, $header_size);
      if ( $body === false ) $body = "";
      curl_close($ch);
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

        $ret = $LastBODYMethod . " Used: " . $LastBODYImpl . "\n" .
    		 "HTTP Response Code: " . $last_http_response . "\n" .
    	     $LastBODYURL . "\n" .
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
        global $LastGETImpl;
        global $LastHeadersReceived;

        $ret = "GET Used: " . $LastGETImpl . "\n" .
    		 "HTTP Response: " . $last_http_response . "\n" .
    	     $LastGETURL . "\n" .
    		 $LastHeadersReceived . "\n";
    	return $ret;
    }

    // Sadly this tries several approaches depending on
    // the PHP version and configuration.  You can use only one
    // if you know what version of PHP is working and how it will be
    // configured...
    public static function doBody($url, $method, $body, $header) {
        global $LastBODYURL;
        global $LastBODYMethod;
        global $LastBODYImpl;
        global $LastHeadersSent;
        global $last_http_response;
        global $LastHeadersReceived;
        global $LastBODYContent;

    	$LastBODYURL = $url;
        $LastBODYMethod = $method;
        $LastBODYImpl = false;
        $LastHeadersSent = false;
        $last_http_response = false;
        $LastHeadersReceived = false;
        $LastBODYContent = false;

        // Prefer curl because it checks if it works before trying
        $LastBODYContent = NET::bodyCurl($url, $method, $body, $header);
        $LastBODYImpl = "CURL";
        if ( $LastBODYContent !== false ) return $LastBODYContent;
        $LastBODYContent = NET::bodySocket($url, $method, $body, $header);
        $LastBODYImpl = "Socket";
        if ( $LastBODYContent !== false ) return $LastBODYContent;
        $LastBODYContent = NET::bodyStream($url, $method, $body, $header);
        $LastBODYImpl = "Stream";
        if ( $LastBODYContent !== false ) return $LastBODYContent;
        $LastBODYImpl = "Error";
        error_log("Unable to $method Url=$url");
        error_log("Header: $header");
        error_log("Body: $body");
        throw new \Exception("Unable to $method $url");
    }

    // From: http://php.net/manual/en/function.file-get-contents.php
    public static function bodySocket($endpoint, $method, $data, $moreheaders=false) {
      if ( ! function_exists('fsockopen') ) return false;
      if ( ! function_exists('stream_get_transports') ) return false;
        $url = parse_url($endpoint);

        if (!isset($url['port'])) {
          if ($url['scheme'] == 'http') { $url['port']=80; }
          elseif ($url['scheme'] == 'https') { $url['port']=443; }
        }

        $url['query']=isset($url['query'])?$url['query']:'';

        $hostport = ':'.$url['port'];
        if ($url['scheme'] == 'http' && $hostport == ':80' ) $hostport = '';
        if ($url['scheme'] == 'https' && $hostport == ':443' ) $hostport = '';

        $url['protocol']=$url['scheme'].'://';
        $eol="\r\n";

        $uri = "/";
        if ( isset($url['path'])) $uri = $url['path'];
        if ( strlen($url['query']) > 0 ) $uri .= '?'.$url['query'];
        if ( strlen($url['fragment']) > 0 ) $uri .= '#'.$url['fragment'];

        $headers =  $method." ".$uri." HTTP/1.0".$eol.
                    "Host: ".$url['host'].$hostport.$eol.
                    "Referer: ".$url['protocol'].$url['host'].$url['path'].$eol.
                    "Content-Length: ".strlen($data).$eol;
        if ( is_string($moreheaders) ) $headers .= $moreheaders;
        $len = strlen($headers);
        if ( substr($headers,$len-2) != $eol ) {
            $headers .= $eol;
        }
        $headers .= $eol.$data;
        // echo("\n"); echo($headers); echo("\n");
        // echo("PORT=".$url['port']);
        $hostname = $url['host'];
        if ( $url['port'] == 443 ) $hostname = "ssl://" . $hostname;
        try {
            $fp = fsockopen($hostname, $url['port'], $errno, $errstr, 30);
            if($fp) {
                fputs($fp, $headers);
                $result = '';
                while(!feof($fp)) { $result .= fgets($fp, 128); }
                fclose($fp);
                // removes HTTP response headers
                $pattern="/^.*\r\n\r\n/s";
                $result=preg_replace($pattern,'',$result);
                return $result;
            }
        } catch(Exception $e) {
            return false;
        }
        return false;
    }

    public static function bodyStream($url, $method, $body, $header) {
        $params = array('http' => array(
            'method' => $method,
            'content' => $body,
            'header' => $header
            ));

        $ctx = stream_context_create($params);
        try {
            $fp = @fopen($url, 'r', false, $ctx);
            $response = @stream_get_contents($fp);
        } catch (Exception $e) {
            return false;
        }
        return $response;
    }

    public static function bodyCurl($url, $method, $body, $header) {
      if ( ! function_exists('curl_init') ) return false;
      global $last_http_response;
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
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
      curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

      // Send to remote and return data to caller.
      $result = curl_exec($ch);
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
      curl_close($ch);
      return $body;
    }

}
