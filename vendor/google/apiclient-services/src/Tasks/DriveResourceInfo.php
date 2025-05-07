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

namespace Google\Service\Tasks;

class DriveResourceInfo extends \Google\Model
{
  /**
   * @var string
   */
  public $driveFileId;
  /**
   * @var string
   */
  public $resourceKey;

  /**
   * @param string
   */
  public function setDriveFileId($driveFileId)
  {
    $this->driveFileId = $driveFileId;
  }
  /**
   * @return string
   */
  public function getDriveFileId()
  {
    return $this->driveFileId;
  }
  /**
   * @param string
   */
  public function setResourceKey($resourceKey)
  {
    $this->resourceKey = $resourceKey;
  }
  /**
   * @return string
   */
  public function getResourceKey()
  {
    return $this->resourceKey;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DriveResourceInfo::class, 'Google_Service_Tasks_DriveResourceInfo');
