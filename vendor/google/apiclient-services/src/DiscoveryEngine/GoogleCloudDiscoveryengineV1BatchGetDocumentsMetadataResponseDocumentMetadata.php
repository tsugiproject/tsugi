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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadata extends \Google\Model
{
  /**
   * @var string
   */
  public $dataIngestionSource;
  /**
   * @var string
   */
  public $lastRefreshedTime;
  protected $matcherValueType = GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadataMatcherValue::class;
  protected $matcherValueDataType = '';
  /**
   * @var string
   */
  public $state;

  /**
   * @param string
   */
  public function setDataIngestionSource($dataIngestionSource)
  {
    $this->dataIngestionSource = $dataIngestionSource;
  }
  /**
   * @return string
   */
  public function getDataIngestionSource()
  {
    return $this->dataIngestionSource;
  }
  /**
   * @param string
   */
  public function setLastRefreshedTime($lastRefreshedTime)
  {
    $this->lastRefreshedTime = $lastRefreshedTime;
  }
  /**
   * @return string
   */
  public function getLastRefreshedTime()
  {
    return $this->lastRefreshedTime;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadataMatcherValue
   */
  public function setMatcherValue(GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadataMatcherValue $matcherValue)
  {
    $this->matcherValue = $matcherValue;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadataMatcherValue
   */
  public function getMatcherValue()
  {
    return $this->matcherValue;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadata::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1BatchGetDocumentsMetadataResponseDocumentMetadata');
