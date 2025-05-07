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

class GoogleCloudDiscoveryengineV1alphaPurgeCompletionSuggestionsResponse extends \Google\Collection
{
  protected $collection_key = 'errorSamples';
  protected $errorSamplesType = GoogleRpcStatus::class;
  protected $errorSamplesDataType = 'array';
  /**
   * @var bool
   */
  public $purgeSucceeded;

  /**
   * @param GoogleRpcStatus[]
   */
  public function setErrorSamples($errorSamples)
  {
    $this->errorSamples = $errorSamples;
  }
  /**
   * @return GoogleRpcStatus[]
   */
  public function getErrorSamples()
  {
    return $this->errorSamples;
  }
  /**
   * @param bool
   */
  public function setPurgeSucceeded($purgeSucceeded)
  {
    $this->purgeSucceeded = $purgeSucceeded;
  }
  /**
   * @return bool
   */
  public function getPurgeSucceeded()
  {
    return $this->purgeSucceeded;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1alphaPurgeCompletionSuggestionsResponse::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1alphaPurgeCompletionSuggestionsResponse');
