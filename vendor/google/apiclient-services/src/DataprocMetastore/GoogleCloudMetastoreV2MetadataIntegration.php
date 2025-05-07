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

class GoogleCloudMetastoreV2MetadataIntegration extends \Google\Model
{
  protected $dataCatalogConfigType = GoogleCloudMetastoreV2DataCatalogConfig::class;
  protected $dataCatalogConfigDataType = '';

  /**
   * @param GoogleCloudMetastoreV2DataCatalogConfig
   */
  public function setDataCatalogConfig(GoogleCloudMetastoreV2DataCatalogConfig $dataCatalogConfig)
  {
    $this->dataCatalogConfig = $dataCatalogConfig;
  }
  /**
   * @return GoogleCloudMetastoreV2DataCatalogConfig
   */
  public function getDataCatalogConfig()
  {
    return $this->dataCatalogConfig;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudMetastoreV2MetadataIntegration::class, 'Google_Service_DataprocMetastore_GoogleCloudMetastoreV2MetadataIntegration');
