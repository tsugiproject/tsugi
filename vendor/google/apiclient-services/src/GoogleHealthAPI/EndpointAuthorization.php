<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\GoogleHealthAPI;

class EndpointAuthorization extends \Google\Model
{
  /**
   * Required. Input only. Provides a client-provided secret that will be sent
   * with each notification to the subscriber endpoint using the "Authorization"
   * header. The value must include the authorization scheme, e.g., "Bearer " or
   * "Basic ", as it will be used as the full Authorization header value. This
   * secret is used by the API to test the endpoint during `CreateSubscriber`
   * and `UpdateSubscriber` calls, and will be sent in the `Authorization`
   * header for all subsequent webhook notifications to this endpoint.
   *
   * @var string
   */
  public $secret;
  /**
   * Output only. Whether the secret is set.
   *
   * @var bool
   */
  public $secretSet;

  /**
   * Required. Input only. Provides a client-provided secret that will be sent
   * with each notification to the subscriber endpoint using the "Authorization"
   * header. The value must include the authorization scheme, e.g., "Bearer " or
   * "Basic ", as it will be used as the full Authorization header value. This
   * secret is used by the API to test the endpoint during `CreateSubscriber`
   * and `UpdateSubscriber` calls, and will be sent in the `Authorization`
   * header for all subsequent webhook notifications to this endpoint.
   *
   * @param string $secret
   */
  public function setSecret($secret)
  {
    $this->secret = $secret;
  }
  /**
   * @return string
   */
  public function getSecret()
  {
    return $this->secret;
  }
  /**
   * Output only. Whether the secret is set.
   *
   * @param bool $secretSet
   */
  public function setSecretSet($secretSet)
  {
    $this->secretSet = $secretSet;
  }
  /**
   * @return bool
   */
  public function getSecretSet()
  {
    return $this->secretSet;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EndpointAuthorization::class, 'Google_Service_GoogleHealthAPI_EndpointAuthorization');
