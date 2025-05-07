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

class GoogleCloudMetastoreV1alphaLocationMetadata extends \Google\Collection
{
  protected $collection_key = 'supportedHiveMetastoreVersions';
  protected $customRegionMetadataType = GoogleCloudMetastoreV1alphaCustomRegionMetadata::class;
  protected $customRegionMetadataDataType = 'array';
  protected $multiRegionMetadataType = GoogleCloudMetastoreV1alphaMultiRegionMetadata::class;
  protected $multiRegionMetadataDataType = '';
  protected $supportedHiveMetastoreVersionsType = GoogleCloudMetastoreV1alphaHiveMetastoreVersion::class;
  protected $supportedHiveMetastoreVersionsDataType = 'array';

  /**
   * @param GoogleCloudMetastoreV1alphaCustomRegionMetadata[]
   */
  public function setCustomRegionMetadata($customRegionMetadata)
  {
    $this->customRegionMetadata = $customRegionMetadata;
  }
  /**
   * @return GoogleCloudMetastoreV1alphaCustomRegionMetadata[]
   */
  public function getCustomRegionMetadata()
  {
    return $this->customRegionMetadata;
  }
  /**
   * @param GoogleCloudMetastoreV1alphaMultiRegionMetadata
   */
  public function setMultiRegionMetadata(GoogleCloudMetastoreV1alphaMultiRegionMetadata $multiRegionMetadata)
  {
    $this->multiRegionMetadata = $multiRegionMetadata;
  }
  /**
   * @return GoogleCloudMetastoreV1alphaMultiRegionMetadata
   */
  public function getMultiRegionMetadata()
  {
    return $this->multiRegionMetadata;
  }
  /**
   * @param GoogleCloudMetastoreV1alphaHiveMetastoreVersion[]
   */
  public function setSupportedHiveMetastoreVersions($supportedHiveMetastoreVersions)
  {
    $this->supportedHiveMetastoreVersions = $supportedHiveMetastoreVersions;
  }
  /**
   * @return GoogleCloudMetastoreV1alphaHiveMetastoreVersion[]
   */
  public function getSupportedHiveMetastoreVersions()
  {
    return $this->supportedHiveMetastoreVersions;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudMetastoreV1alphaLocationMetadata::class, 'Google_Service_DataprocMetastore_GoogleCloudMetastoreV1alphaLocationMetadata');
