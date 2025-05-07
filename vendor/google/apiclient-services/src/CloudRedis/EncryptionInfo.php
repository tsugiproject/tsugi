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

namespace Google\Service\CloudRedis;

class EncryptionInfo extends \Google\Collection
{
  protected $collection_key = 'kmsKeyVersions';
  /**
   * @var string
   */
  public $encryptionType;
  /**
   * @var string
   */
  public $kmsKeyPrimaryState;
  /**
   * @var string[]
   */
  public $kmsKeyVersions;
  /**
   * @var string
   */
  public $lastUpdateTime;

  /**
   * @param string
   */
  public function setEncryptionType($encryptionType)
  {
    $this->encryptionType = $encryptionType;
  }
  /**
   * @return string
   */
  public function getEncryptionType()
  {
    return $this->encryptionType;
  }
  /**
   * @param string
   */
  public function setKmsKeyPrimaryState($kmsKeyPrimaryState)
  {
    $this->kmsKeyPrimaryState = $kmsKeyPrimaryState;
  }
  /**
   * @return string
   */
  public function getKmsKeyPrimaryState()
  {
    return $this->kmsKeyPrimaryState;
  }
  /**
   * @param string[]
   */
  public function setKmsKeyVersions($kmsKeyVersions)
  {
    $this->kmsKeyVersions = $kmsKeyVersions;
  }
  /**
   * @return string[]
   */
  public function getKmsKeyVersions()
  {
    return $this->kmsKeyVersions;
  }
  /**
   * @param string
   */
  public function setLastUpdateTime($lastUpdateTime)
  {
    $this->lastUpdateTime = $lastUpdateTime;
  }
  /**
   * @return string
   */
  public function getLastUpdateTime()
  {
    return $this->lastUpdateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EncryptionInfo::class, 'Google_Service_CloudRedis_EncryptionInfo');
