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

namespace Google\Service\ChromeManagement;

class GoogleChromeManagementVersionsV1AttestationCredential extends \Google\Model
{
  /**
   * @var string
   */
  public $keyRotationTime;
  /**
   * @var string
   */
  public $keyTrustLevel;
  /**
   * @var string
   */
  public $keyType;
  /**
   * @var string
   */
  public $publicKey;

  /**
   * @param string
   */
  public function setKeyRotationTime($keyRotationTime)
  {
    $this->keyRotationTime = $keyRotationTime;
  }
  /**
   * @return string
   */
  public function getKeyRotationTime()
  {
    return $this->keyRotationTime;
  }
  /**
   * @param string
   */
  public function setKeyTrustLevel($keyTrustLevel)
  {
    $this->keyTrustLevel = $keyTrustLevel;
  }
  /**
   * @return string
   */
  public function getKeyTrustLevel()
  {
    return $this->keyTrustLevel;
  }
  /**
   * @param string
   */
  public function setKeyType($keyType)
  {
    $this->keyType = $keyType;
  }
  /**
   * @return string
   */
  public function getKeyType()
  {
    return $this->keyType;
  }
  /**
   * @param string
   */
  public function setPublicKey($publicKey)
  {
    $this->publicKey = $publicKey;
  }
  /**
   * @return string
   */
  public function getPublicKey()
  {
    return $this->publicKey;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChromeManagementVersionsV1AttestationCredential::class, 'Google_Service_ChromeManagement_GoogleChromeManagementVersionsV1AttestationCredential');
