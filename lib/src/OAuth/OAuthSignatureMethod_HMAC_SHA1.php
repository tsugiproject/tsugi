<?php
// vim: foldmethod=marker

namespace Tsugi\OAuth;

class OAuthSignatureMethod_HMAC_SHA1 extends OAuthSignatureMethod {
  function get_name() {
    return "HMAC-SHA1";
  }

  public function build_signature($request, $consumer, $token) {
    global $OAuth_last_computed_signature;
    $OAuth_last_computed_signature = false;

    $base_string = $request->get_signature_base_string();
    $request->base_string = $base_string;

    $key_parts = array(
      $consumer->secret,
      ($token) ? $token->secret : ""
    );

    $key_parts = OAuthUtil::urlencode_rfc3986($key_parts);
    $key = implode('&', $key_parts);

    $computed_signature = base64_encode(hash_hmac('sha1', $base_string, $key, true));
    $OAuth_last_computed_signature = $computed_signature;
    return $computed_signature;
  }

}
