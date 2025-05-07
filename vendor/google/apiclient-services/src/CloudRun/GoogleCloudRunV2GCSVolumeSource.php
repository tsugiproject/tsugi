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

namespace Google\Service\CloudRun;

class GoogleCloudRunV2GCSVolumeSource extends \Google\Collection
{
  protected $collection_key = 'mountOptions';
  /**
   * @var string
   */
  public $bucket;
  /**
   * @var string[]
   */
  public $mountOptions;
  /**
   * @var bool
   */
  public $readOnly;

  /**
   * @param string
   */
  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  /**
   * @return string
   */
  public function getBucket()
  {
    return $this->bucket;
  }
  /**
   * @param string[]
   */
  public function setMountOptions($mountOptions)
  {
    $this->mountOptions = $mountOptions;
  }
  /**
   * @return string[]
   */
  public function getMountOptions()
  {
    return $this->mountOptions;
  }
  /**
   * @param bool
   */
  public function setReadOnly($readOnly)
  {
    $this->readOnly = $readOnly;
  }
  /**
   * @return bool
   */
  public function getReadOnly()
  {
    return $this->readOnly;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRunV2GCSVolumeSource::class, 'Google_Service_CloudRun_GoogleCloudRunV2GCSVolumeSource');
