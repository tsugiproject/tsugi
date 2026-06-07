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

class GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig extends \Google\Collection
{
  protected $collection_key = 'returnedFields';
  protected $alloydbAiNlConfigType = GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig::class;
  protected $alloydbAiNlConfigDataType = '';
  protected $alloydbConnectionConfigType = GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig::class;
  protected $alloydbConnectionConfigDataType = '';
  /**
   * Optional. Fields to be returned in the search results. If empty, all fields
   * will be returned.
   *
   * @var string[]
   */
  public $returnedFields;

  /**
   * Optional. Configuration for Magic.
   *
   * @param GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig $alloydbAiNlConfig
   */
  public function setAlloydbAiNlConfig(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig $alloydbAiNlConfig)
  {
    $this->alloydbAiNlConfig = $alloydbAiNlConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig
   */
  public function getAlloydbAiNlConfig()
  {
    return $this->alloydbAiNlConfig;
  }
  /**
   * Required. Configuration for connecting to AlloyDB.
   *
   * @param GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig $alloydbConnectionConfig
   */
  public function setAlloydbConnectionConfig(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig $alloydbConnectionConfig)
  {
    $this->alloydbConnectionConfig = $alloydbConnectionConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfigAlloyDbConnectionConfig
   */
  public function getAlloydbConnectionConfig()
  {
    return $this->alloydbConnectionConfig;
  }
  /**
   * Optional. Fields to be returned in the search results. If empty, all fields
   * will be returned.
   *
   * @param string[] $returnedFields
   */
  public function setReturnedFields($returnedFields)
  {
    $this->returnedFields = $returnedFields;
  }
  /**
   * @return string[]
   */
  public function getReturnedFields()
  {
    return $this->returnedFields;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig');
