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

namespace Google\Service\Connectors;

class AuthCodeData extends \Google\Model
{
  /**
   * @var string
   */
  public $authCode;
  /**
   * @var string
   */
  public $pkceVerifier;
  /**
   * @var string
   */
  public $redirectUri;

  /**
   * @param string
   */
  public function setAuthCode($authCode)
  {
    $this->authCode = $authCode;
  }
  /**
   * @return string
   */
  public function getAuthCode()
  {
    return $this->authCode;
  }
  /**
   * @param string
   */
  public function setPkceVerifier($pkceVerifier)
  {
    $this->pkceVerifier = $pkceVerifier;
  }
  /**
   * @return string
   */
  public function getPkceVerifier()
  {
    return $this->pkceVerifier;
  }
  /**
   * @param string
   */
  public function setRedirectUri($redirectUri)
  {
    $this->redirectUri = $redirectUri;
  }
  /**
   * @return string
   */
  public function getRedirectUri()
  {
    return $this->redirectUri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AuthCodeData::class, 'Google_Service_Connectors_AuthCodeData');
