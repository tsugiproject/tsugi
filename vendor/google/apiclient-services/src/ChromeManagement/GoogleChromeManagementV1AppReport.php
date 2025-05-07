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

namespace Google\Service\ChromeManagement;

class GoogleChromeManagementV1AppReport extends \Google\Collection
{
  protected $collection_key = 'usageData';
  /**
   * @var string
   */
  public $reportTime;
  protected $usageDataType = GoogleChromeManagementV1AppUsageData::class;
  protected $usageDataDataType = 'array';

  /**
   * @param string
   */
  public function setReportTime($reportTime)
  {
    $this->reportTime = $reportTime;
  }
  /**
   * @return string
   */
  public function getReportTime()
  {
    return $this->reportTime;
  }
  /**
   * @param GoogleChromeManagementV1AppUsageData[]
   */
  public function setUsageData($usageData)
  {
    $this->usageData = $usageData;
  }
  /**
   * @return GoogleChromeManagementV1AppUsageData[]
   */
  public function getUsageData()
  {
    return $this->usageData;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChromeManagementV1AppReport::class, 'Google_Service_ChromeManagement_GoogleChromeManagementV1AppReport');
