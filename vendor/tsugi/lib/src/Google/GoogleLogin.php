<?php 

namespace Tsugi\Google;
use \Tsugi\Google\JWT;

/** A very simple Google Login
 *
 * This is a simple wrapper class to hold some Google Login 
 * functionalty.  I wrapped this in a class to avoid namespace
 * conflicts with the common function names in this code.
 *
 * It is based on this blog post:
 *
 * http://www.nmecs.com/articles/google-single-sign-on-without-sdk
 *
 * See docs/LOGIN.md in the tsugi repo for details on how to set this up with Tsugi.
 */

class GoogleLogin {

    public $client_id = false;
    public $client_secret = false;
    public $openid_realm = false;
    public $redirect = false;
    public $access_token = false;
    public $last_response = false;
    public $authentication_object = false;

    public function __construct($client_id, $client_secret, $redirect, $openid_realm=false) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->redirect = $redirect;
        $this->openid_realm = $openid_realm;
    }

    /**
     * Get the login url
     */
    function getLoginUrl($state) {
        $loginUrl = "https://accounts.google.com/o/oauth2/auth?"
            . "client_id=" . $this->client_id
            . "&redirect_uri=" . $this->redirect
            . "&state=" . $state
            . "&response_type=code"
            . "&scope=email%20profile" 
            . "&include_granted_scopes=true";

        if ( $this->openid_realm ) {
            $loginUrl .= "&openid.realm=" . $this->openid_realm;
        }
        return $loginUrl;
    }

    /**
      * Get the access token
      */
    function getAccessToken($google_code) {
        // Make sure these are clear if we fail
        $this->authentication_object = FALSE;
        $this->access_token = FALSE;

        // From: https://github.com/PenguinProtocols/Basic-OpenID-Connect-Google
        // This approach gets us the openid_id from the former realm

        $token_url = "https://www.googleapis.com/oauth2/v3/token";
        $post = "code={$google_code}&client_id={$this->client_id}&client_secret={$this->client_secret}"
            . "&redirect_uri={$this->redirect}&grant_type=authorization_code";

        if ( $this->openid_realm ) {
            $post .= "&openid.realm=" . $this->openid_realm;
        }

        $response = $this->curl_post($token_url, $post);

        /* $response normally looks like this:
            {
                "access_token" : "QEX3L0Fm ... about 80 characters ... 4tLMYze617",
                "token_type" : "Bearer",
                "expires_in" : 3599,
                "id_token" : "hbGciO ... about 700 characers ... t3cE"
            }
        */

        if ($response) {
            $this->authentication_object = json_decode($response);
        }

        if (isset($this->authentication_object->refresh_token)) {
            $this->Google_RefreshToken = $this->authentication_object->refresh_token;
        }
        if (isset($this->authentication_object->access_token)) {
            $this->access_token = $this->authentication_object->access_token;
            return $this->authentication_object;
        } else {
            return FALSE;
        }
    }

    /*
     * Retieve the profile information
     */
    function getUserInfo($access_token=FALSE) {
        if ( $access_token === FALSE ) $access_token = $this->access_token;
        if ( $access_token === FALSE ) return FALSE;

        $user_info_url = "https://www.googleapis.com/oauth2/v1/userinfo?alt=json&access_token=" . 
            $access_token;

        $response = $this->curl_get($user_info_url);

        /* $response looks like this: 
            {
                "id": "105526282828383882908",
                "email": "dr.chuck@gmail.com",
                "verified_email": true,
                "name": "Chuck Severance",
                "given_name": "Chuck",
                "family_name": "Severance",
                "link": "https://plus.google.com/+ChuckSeverance",
                "picture": "https://lh4.googleusercontent.com/sWWd ... Fr9lw/photo.jpg",
                "gender": "male",
                "locale": "en"
            }
        */

        $user = json_decode($response);
        if ( ! $user ) return FALSE;

        // Get the old openid_id if we asked for and received it
        $user->openid_id = false;
        if ( $this->authentication_object && isset($this->authentication_object->id_token) ) {
            $id_token = $this->authentication_object->id_token;
            $info = JWT::decode($id_token, $this->client_secret, false);
            if ( $info && isset($info->sub) && isset($info->openid_id) ) {
                $user->openid_id = $info->openid_id;
            }
        }
        return $user;
    }

    /**
      * Utility code in order not to have external dependencies
      * and to have some logging.
      */
    function curl_post($url, $post) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $json_response = curl_exec($curl);
        $this->last_response = $json_response;
        curl_close($curl);
        return $json_response;
    }

    /**
      * Utility code in order not to have external dependencies
      * and to have some logging.
      */
    function curl_get($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1);
        $json_response = curl_exec($curl);
        $this->last_response = $json_response;
        curl_close($curl);
        return $json_response;
    }


}
