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

namespace Google\Service\WorkloadManager;

class CloudAsset extends \Google\Model
{
  /**
   * @var string
   */
  public $assetName;
  /**
   * @var string
   */
  public $assetType;

  /**
   * @param string
   */
  public function setAssetName($assetName)
  {
    $this->assetName = $assetName;
  }
  /**
   * @return string
   */
  public function getAssetName()
  {
    return $this->assetName;
  }
  /**
   * @param string
   */
  public function setAssetType($assetType)
  {
    $this->assetType = $assetType;
  }
  /**
   * @return string
   */
  public function getAssetType()
  {
    return $this->assetType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CloudAsset::class, 'Google_Service_WorkloadManager_CloudAsset');
