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

namespace Google\Service\Integrations;

class GoogleCloudIntegrationsV1alphaUseTemplateResponse extends \Google\Collection
{
  protected $collection_key = 'subIntegrationVersions';
  protected $integrationVersionType = GoogleCloudIntegrationsV1alphaIntegrationVersion::class;
  protected $integrationVersionDataType = '';
  protected $subIntegrationVersionsType = GoogleCloudIntegrationsV1alphaIntegrationVersion::class;
  protected $subIntegrationVersionsDataType = 'array';

  /**
   * @param GoogleCloudIntegrationsV1alphaIntegrationVersion
   */
  public function setIntegrationVersion(GoogleCloudIntegrationsV1alphaIntegrationVersion $integrationVersion)
  {
    $this->integrationVersion = $integrationVersion;
  }
  /**
   * @return GoogleCloudIntegrationsV1alphaIntegrationVersion
   */
  public function getIntegrationVersion()
  {
    return $this->integrationVersion;
  }
  /**
   * @param GoogleCloudIntegrationsV1alphaIntegrationVersion[]
   */
  public function setSubIntegrationVersions($subIntegrationVersions)
  {
    $this->subIntegrationVersions = $subIntegrationVersions;
  }
  /**
   * @return GoogleCloudIntegrationsV1alphaIntegrationVersion[]
   */
  public function getSubIntegrationVersions()
  {
    return $this->subIntegrationVersions;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudIntegrationsV1alphaUseTemplateResponse::class, 'Google_Service_Integrations_GoogleCloudIntegrationsV1alphaUseTemplateResponse');
