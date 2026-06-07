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

class GoogleCloudDiscoveryengineV1ListLicenseConfigsResponse extends \Google\Collection
{
  protected $collection_key = 'licenseConfigs';
  protected $licenseConfigsType = GoogleCloudDiscoveryengineV1LicenseConfig::class;
  protected $licenseConfigsDataType = 'array';
  /**
   * Not supported.
   *
   * @var string
   */
  public $nextPageToken;

  /**
   * All the customer's LicenseConfigs.
   *
   * @param GoogleCloudDiscoveryengineV1LicenseConfig[] $licenseConfigs
   */
  public function setLicenseConfigs($licenseConfigs)
  {
    $this->licenseConfigs = $licenseConfigs;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1LicenseConfig[]
   */
  public function getLicenseConfigs()
  {
    return $this->licenseConfigs;
  }
  /**
   * Not supported.
   *
   * @param string $nextPageToken
   */
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  /**
   * @return string
   */
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1ListLicenseConfigsResponse::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1ListLicenseConfigsResponse');
