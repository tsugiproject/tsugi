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

class BulkEditAdGroupAssignedTargetingOptionsRequest extends \Google\Collection
{
  protected $collection_key = 'deleteRequests';
  /**
   * Required. The IDs of the ad groups the assigned targeting options will
   * belong to. A maximum of 25 ad group IDs can be specified.
   *
   * @var string[]
   */
  public $adGroupIds;
  protected $createRequestsType = CreateAssignedTargetingOptionsRequest::class;
  protected $createRequestsDataType = 'array';
  protected $deleteRequestsType = DeleteAssignedTargetingOptionsRequest::class;
  protected $deleteRequestsDataType = 'array';

  /**
   * Required. The IDs of the ad groups the assigned targeting options will
   * belong to. A maximum of 25 ad group IDs can be specified.
   *
   * @param string[] $adGroupIds
   */
  public function setAdGroupIds($adGroupIds)
  {
    $this->adGroupIds = $adGroupIds;
  }
  /**
   * @return string[]
   */
  public function getAdGroupIds()
  {
    return $this->adGroupIds;
  }
  /**
   * Optional. The assigned targeting options to create in batch, specified as a
   * list of `CreateAssignedTargetingOptionRequest`. Supported targeting types:
   * * `TARGETING_TYPE_AGE_RANGE` * `TARGETING_TYPE_APP` *
   * `TARGETING_TYPE_APP_CATEGORY` * `TARGETING_TYPE_AUDIENCE_GROUP` *
   * `TARGETING_TYPE_CATEGORY` * `TARGETING_TYPE_GENDER` *
   * `TARGETING_TYPE_GEO_REGION` * `TARGETING_TYPE_HOUSEHOLD_INCOME` *
   * `TARGETING_TYPE_KEYWORD` * `TARGETING_TYPE_LANGUAGE` *
   * `TARGETING_TYPE_PARENTAL_STATUS` * `TARGETING_TYPE_URL` *
   * `TARGETING_TYPE_YOUTUBE_CHANNEL` * `TARGETING_TYPE_YOUTUBE_VIDEO`
   *
   * @param CreateAssignedTargetingOptionsRequest[] $createRequests
   */
  public function setCreateRequests($createRequests)
  {
    $this->createRequests = $createRequests;
  }
  /**
   * @return CreateAssignedTargetingOptionsRequest[]
   */
  public function getCreateRequests()
  {
    return $this->createRequests;
  }
  /**
   * Optional. The assigned targeting options to delete in batch, specified as a
   * list of `DeleteAssignedTargetingOptionsRequest`. Supported targeting types:
   * * `TARGETING_TYPE_AGE_RANGE` * `TARGETING_TYPE_APP` *
   * `TARGETING_TYPE_APP_CATEGORY` * `TARGETING_TYPE_AUDIENCE_GROUP` *
   * `TARGETING_TYPE_CATEGORY` * `TARGETING_TYPE_GENDER` *
   * `TARGETING_TYPE_GEO_REGION` * `TARGETING_TYPE_HOUSEHOLD_INCOME` *
   * `TARGETING_TYPE_KEYWORD` * `TARGETING_TYPE_LANGUAGE` *
   * `TARGETING_TYPE_PARENTAL_STATUS` * `TARGETING_TYPE_URL` *
   * `TARGETING_TYPE_YOUTUBE_CHANNEL` * `TARGETING_TYPE_YOUTUBE_VIDEO`
   *
   * @param DeleteAssignedTargetingOptionsRequest[] $deleteRequests
   */
  public function setDeleteRequests($deleteRequests)
  {
    $this->deleteRequests = $deleteRequests;
  }
  /**
   * @return DeleteAssignedTargetingOptionsRequest[]
   */
  public function getDeleteRequests()
  {
    return $this->deleteRequests;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BulkEditAdGroupAssignedTargetingOptionsRequest::class, 'Google_Service_DisplayVideo_BulkEditAdGroupAssignedTargetingOptionsRequest');
