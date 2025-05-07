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

namespace Google\Service\Baremetalsolution;

class ReimageInstanceRequest extends \Google\Collection
{
  protected $collection_key = 'sshKeys';
  /**
   * @var string
   */
  public $kmsKeyVersion;
  /**
   * @var string
   */
  public $osImage;
  /**
   * @var string[]
   */
  public $sshKeys;

  /**
   * @param string
   */
  public function setKmsKeyVersion($kmsKeyVersion)
  {
    $this->kmsKeyVersion = $kmsKeyVersion;
  }
  /**
   * @return string
   */
  public function getKmsKeyVersion()
  {
    return $this->kmsKeyVersion;
  }
  /**
   * @param string
   */
  public function setOsImage($osImage)
  {
    $this->osImage = $osImage;
  }
  /**
   * @return string
   */
  public function getOsImage()
  {
    return $this->osImage;
  }
  /**
   * @param string[]
   */
  public function setSshKeys($sshKeys)
  {
    $this->sshKeys = $sshKeys;
  }
  /**
   * @return string[]
   */
  public function getSshKeys()
  {
    return $this->sshKeys;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReimageInstanceRequest::class, 'Google_Service_Baremetalsolution_ReimageInstanceRequest');
