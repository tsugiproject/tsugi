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

class GoogleCloudDiscoveryengineV1DataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig extends \Google\Model
{
  /**
   * Optional. AlloyDb AI NL config id, i.e. the value that was used for calling
   * `SELECT alloydb_ai_nl.g_create_configuration(...)`. Can be empty.
   *
   * @var string
   */
  public $nlConfigId;

  /**
   * Optional. AlloyDb AI NL config id, i.e. the value that was used for calling
   * `SELECT alloydb_ai_nl.g_create_configuration(...)`. Can be empty.
   *
   * @param string $nlConfigId
   */
  public function setNlConfigId($nlConfigId)
  {
    $this->nlConfigId = $nlConfigId;
  }
  /**
   * @return string
   */
  public function getNlConfigId()
  {
    return $this->nlConfigId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1DataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1DataStoreFederatedSearchConfigAlloyDbConfigAlloyDbAiNaturalLanguageConfig');
