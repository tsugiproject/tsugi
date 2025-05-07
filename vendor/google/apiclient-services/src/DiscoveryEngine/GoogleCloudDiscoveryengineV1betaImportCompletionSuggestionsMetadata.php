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

class GoogleCloudDiscoveryengineV1betaImportCompletionSuggestionsMetadata extends \Google\Model
{
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $failureCount;
  /**
   * @var string
   */
  public $successCount;
  /**
   * @var string
   */
  public $updateTime;

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
  public function setFailureCount($failureCount)
  {
    $this->failureCount = $failureCount;
  }
  /**
   * @return string
   */
  public function getFailureCount()
  {
    return $this->failureCount;
  }
  /**
   * @param string
   */
  public function setSuccessCount($successCount)
  {
    $this->successCount = $successCount;
  }
  /**
   * @return string
   */
  public function getSuccessCount()
  {
    return $this->successCount;
  }
  /**
   * @param string
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1betaImportCompletionSuggestionsMetadata::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1betaImportCompletionSuggestionsMetadata');
