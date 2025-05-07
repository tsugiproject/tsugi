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

namespace Google\Service\CloudIAP;

class OAuth2 extends \Google\Model
{
  /**
   * @var string
   */
  public $clientId;
  /**
   * @var string
   */
  public $clientSecret;
  /**
   * @var string
   */
  public $clientSecretSha256;

  /**
   * @param string
   */
  public function setClientId($clientId)
  {
    $this->clientId = $clientId;
  }
  /**
   * @return string
   */
  public function getClientId()
  {
    return $this->clientId;
  }
  /**
   * @param string
   */
  public function setClientSecret($clientSecret)
  {
    $this->clientSecret = $clientSecret;
  }
  /**
   * @return string
   */
  public function getClientSecret()
  {
    return $this->clientSecret;
  }
  /**
   * @param string
   */
  public function setClientSecretSha256($clientSecretSha256)
  {
    $this->clientSecretSha256 = $clientSecretSha256;
  }
  /**
   * @return string
   */
  public function getClientSecretSha256()
  {
    return $this->clientSecretSha256;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(OAuth2::class, 'Google_Service_CloudIAP_OAuth2');
