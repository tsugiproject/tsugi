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

class GoogleCloudDiscoveryengineV1alphaDedicatedCrawlRateTimeSeries extends \Google\Model
{
  protected $autoRefreshCrawlRateType = GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries::class;
  protected $autoRefreshCrawlRateDataType = '';
  protected $userTriggeredCrawlRateType = GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries::class;
  protected $userTriggeredCrawlRateDataType = '';

  /**
   * @param GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries
   */
  public function setAutoRefreshCrawlRate(GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries $autoRefreshCrawlRate)
  {
    $this->autoRefreshCrawlRate = $autoRefreshCrawlRate;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries
   */
  public function getAutoRefreshCrawlRate()
  {
    return $this->autoRefreshCrawlRate;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries
   */
  public function setUserTriggeredCrawlRate(GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries $userTriggeredCrawlRate)
  {
    $this->userTriggeredCrawlRate = $userTriggeredCrawlRate;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1alphaCrawlRateTimeSeries
   */
  public function getUserTriggeredCrawlRate()
  {
    return $this->userTriggeredCrawlRate;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1alphaDedicatedCrawlRateTimeSeries::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1alphaDedicatedCrawlRateTimeSeries');
