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

class GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadata extends \Google\Collection
{
  protected $collection_key = 'partialErrors';
  /**
   * @var string
   */
  public $createTime;
  protected $downloadStatsType = GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadataDownloadStats::class;
  protected $downloadStatsDataType = '';
  /**
   * @var string
   */
  public $endTime;
  protected $partialErrorsType = GoogleRpcStatus::class;
  protected $partialErrorsDataType = 'array';
  protected $requestType = GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsRequest::class;
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
   * @param GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadataDownloadStats
   */
  public function setDownloadStats(GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadataDownloadStats $downloadStats)
  {
    $this->downloadStats = $downloadStats;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadataDownloadStats
   */
  public function getDownloadStats()
  {
    return $this->downloadStats;
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
   * @param GoogleRpcStatus[]
   */
  public function setPartialErrors($partialErrors)
  {
    $this->partialErrors = $partialErrors;
  }
  /**
   * @return GoogleRpcStatus[]
   */
  public function getPartialErrors()
  {
    return $this->partialErrors;
  }
  /**
   * @param GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsRequest
   */
  public function setRequest(GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsRequest $request)
  {
    $this->request = $request;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsRequest
   */
  public function getRequest()
  {
    return $this->request;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadata::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1BulkDownloadFeedbackLabelsMetadata');
