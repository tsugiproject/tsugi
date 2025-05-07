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

namespace Google\Service\Spanner;

class CopyBackupEncryptionConfig extends \Google\Collection
{
  protected $collection_key = 'kmsKeyNames';
  /**
   * @var string
   */
  public $encryptionType;
  /**
   * @var string
   */
  public $kmsKeyName;
  /**
   * @var string[]
   */
  public $kmsKeyNames;

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
  public function setKmsKeyName($kmsKeyName)
  {
    $this->kmsKeyName = $kmsKeyName;
  }
  /**
   * @return string
   */
  public function getKmsKeyName()
  {
    return $this->kmsKeyName;
  }
  /**
   * @param string[]
   */
  public function setKmsKeyNames($kmsKeyNames)
  {
    $this->kmsKeyNames = $kmsKeyNames;
  }
  /**
   * @return string[]
   */
  public function getKmsKeyNames()
  {
    return $this->kmsKeyNames;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CopyBackupEncryptionConfig::class, 'Google_Service_Spanner_CopyBackupEncryptionConfig');
