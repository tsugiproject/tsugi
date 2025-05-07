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

namespace Google\Service\Contactcenterinsights;

class GoogleCloudContactcenterinsightsV1alpha1CreateIssueMetadata extends \Google\Model
{
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $endTime;
  protected $requestType = GoogleCloudContactcenterinsightsV1alpha1CreateIssueRequest::class;
  protected $requestDataType = '';

  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param string
   */
  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  /**
   * @return string
   */
  public function getEndTime()
  {
    return $this->endTime;
  }
  /**
   * @param GoogleCloudContactcenterinsightsV1alpha1CreateIssueRequest
   */
  public function setRequest(GoogleCloudContactcenterinsightsV1alpha1CreateIssueRequest $request)
  {
    $this->request = $request;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1alpha1CreateIssueRequest
   */
  public function getRequest()
  {
    return $this->request;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1alpha1CreateIssueMetadata::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1alpha1CreateIssueMetadata');
