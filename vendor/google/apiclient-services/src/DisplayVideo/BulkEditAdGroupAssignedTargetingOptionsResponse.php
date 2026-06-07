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

namespace Google\Service\DisplayVideo;

class BulkEditAdGroupAssignedTargetingOptionsResponse extends \Google\Collection
{
  protected $collection_key = 'updatedAdGroupIds';
  protected $errorsType = Status::class;
  protected $errorsDataType = 'array';
  /**
   * Output only. The IDs of the ad groups which failed to update.
   *
   * @var string[]
   */
  public $failedAdGroupIds;
  /**
   * Output only. The IDs of the ad groups which were successfully updated.
   *
   * @var string[]
   */
  public $updatedAdGroupIds;

  /**
   * Output only. The error information for each ad group that failed to update.
   *
   * @param Status[] $errors
   */
  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  /**
   * @return Status[]
   */
  public function getErrors()
  {
    return $this->errors;
  }
  /**
   * Output only. The IDs of the ad groups which failed to update.
   *
   * @param string[] $failedAdGroupIds
   */
  public function setFailedAdGroupIds($failedAdGroupIds)
  {
    $this->failedAdGroupIds = $failedAdGroupIds;
  }
  /**
   * @return string[]
   */
  public function getFailedAdGroupIds()
  {
    return $this->failedAdGroupIds;
  }
  /**
   * Output only. The IDs of the ad groups which were successfully updated.
   *
   * @param string[] $updatedAdGroupIds
   */
  public function setUpdatedAdGroupIds($updatedAdGroupIds)
  {
    $this->updatedAdGroupIds = $updatedAdGroupIds;
  }
  /**
   * @return string[]
   */
  public function getUpdatedAdGroupIds()
  {
    return $this->updatedAdGroupIds;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BulkEditAdGroupAssignedTargetingOptionsResponse::class, 'Google_Service_DisplayVideo_BulkEditAdGroupAssignedTargetingOptionsResponse');
