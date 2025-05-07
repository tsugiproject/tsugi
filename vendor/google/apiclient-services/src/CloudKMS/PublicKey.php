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

namespace Google\Service\CloudKMS;

class PublicKey extends \Google\Model
{
  /**
   * @var string
   */
  public $algorithm;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $pem;
  /**
   * @var string
   */
  public $pemCrc32c;
  /**
   * @var string
   */
  public $protectionLevel;
  protected $publicKeyType = ChecksummedData::class;
  protected $publicKeyDataType = '';
  /**
   * @var string
   */
  public $publicKeyFormat;

  /**
   * @param string
   */
  public function setAlgorithm($algorithm)
  {
    $this->algorithm = $algorithm;
  }
  /**
   * @return string
   */
  public function getAlgorithm()
  {
    return $this->algorithm;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param string
   */
  public function setPem($pem)
  {
    $this->pem = $pem;
  }
  /**
   * @return string
   */
  public function getPem()
  {
    return $this->pem;
  }
  /**
   * @param string
   */
  public function setPemCrc32c($pemCrc32c)
  {
    $this->pemCrc32c = $pemCrc32c;
  }
  /**
   * @return string
   */
  public function getPemCrc32c()
  {
    return $this->pemCrc32c;
  }
  /**
   * @param string
   */
  public function setProtectionLevel($protectionLevel)
  {
    $this->protectionLevel = $protectionLevel;
  }
  /**
   * @return string
   */
  public function getProtectionLevel()
  {
    return $this->protectionLevel;
  }
  /**
   * @param ChecksummedData
   */
  public function setPublicKey(ChecksummedData $publicKey)
  {
    $this->publicKey = $publicKey;
  }
  /**
   * @return ChecksummedData
   */
  public function getPublicKey()
  {
    return $this->publicKey;
  }
  /**
   * @param string
   */
  public function setPublicKeyFormat($publicKeyFormat)
  {
    $this->publicKeyFormat = $publicKeyFormat;
  }
  /**
   * @return string
   */
  public function getPublicKeyFormat()
  {
    return $this->publicKeyFormat;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PublicKey::class, 'Google_Service_CloudKMS_PublicKey');
