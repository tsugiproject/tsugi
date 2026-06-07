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

class GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfig extends \Google\Model
{
  protected $alloyDbConfigType = GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig::class;
  protected $alloyDbConfigDataType = '';
  protected $notebooklmConfigType = GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigNotebooklmConfig::class;
  protected $notebooklmConfigDataType = '';
  protected $thirdPartyOauthConfigType = GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigThirdPartyOauthConfig::class;
  protected $thirdPartyOauthConfigDataType = '';

  /**
   * AlloyDB config. If set, this DataStore is connected to AlloyDB.
   *
   * @param GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig $alloyDbConfig
   */
  public function setAlloyDbConfig(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig $alloyDbConfig)
  {
    $this->alloyDbConfig = $alloyDbConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigAlloyDbConfig
   */
  public function getAlloyDbConfig()
  {
    return $this->alloyDbConfig;
  }
  /**
   * NotebookLM config. If set, this DataStore is connected to NotebookLM
   * Enterprise.
   *
   * @param GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigNotebooklmConfig $notebooklmConfig
   */
  public function setNotebooklmConfig(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigNotebooklmConfig $notebooklmConfig)
  {
    $this->notebooklmConfig = $notebooklmConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigNotebooklmConfig
   */
  public function getNotebooklmConfig()
  {
    return $this->notebooklmConfig;
  }
  /**
   * Third Party OAuth config. If set, this DataStore is connected to a third
   * party application.
   *
   * @param GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigThirdPartyOauthConfig $thirdPartyOauthConfig
   */
  public function setThirdPartyOauthConfig(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigThirdPartyOauthConfig $thirdPartyOauthConfig)
  {
    $this->thirdPartyOauthConfig = $thirdPartyOauthConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfigThirdPartyOauthConfig
   */
  public function getThirdPartyOauthConfig()
  {
    return $this->thirdPartyOauthConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfig::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1betaDataStoreFederatedSearchConfig');
