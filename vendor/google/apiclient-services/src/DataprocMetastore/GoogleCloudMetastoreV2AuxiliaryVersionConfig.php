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

namespace Google\Service\DataprocMetastore;

class GoogleCloudMetastoreV2AuxiliaryVersionConfig extends \Google\Collection
{
  protected $collection_key = 'endpoints';
  /**
   * @var string[]
   */
  public $configOverrides;
  protected $endpointsType = GoogleCloudMetastoreV2Endpoint::class;
  protected $endpointsDataType = 'array';
  /**
   * @var string
   */
  public $version;

  /**
   * @param string[]
   */
  public function setConfigOverrides($configOverrides)
  {
    $this->configOverrides = $configOverrides;
  }
  /**
   * @return string[]
   */
  public function getConfigOverrides()
  {
    return $this->configOverrides;
  }
  /**
   * @param GoogleCloudMetastoreV2Endpoint[]
   */
  public function setEndpoints($endpoints)
  {
    $this->endpoints = $endpoints;
  }
  /**
   * @return GoogleCloudMetastoreV2Endpoint[]
   */
  public function getEndpoints()
  {
    return $this->endpoints;
  }
  /**
   * @param string
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudMetastoreV2AuxiliaryVersionConfig::class, 'Google_Service_DataprocMetastore_GoogleCloudMetastoreV2AuxiliaryVersionConfig');
