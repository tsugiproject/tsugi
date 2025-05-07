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

class LocationMetadata extends \Google\Collection
{
  protected $collection_key = 'supportedHiveMetastoreVersions';
  protected $customRegionMetadataType = CustomRegionMetadata::class;
  protected $customRegionMetadataDataType = 'array';
  protected $multiRegionMetadataType = MultiRegionMetadata::class;
  protected $multiRegionMetadataDataType = '';
  protected $supportedHiveMetastoreVersionsType = HiveMetastoreVersion::class;
  protected $supportedHiveMetastoreVersionsDataType = 'array';

  /**
   * @param CustomRegionMetadata[]
   */
  public function setCustomRegionMetadata($customRegionMetadata)
  {
    $this->customRegionMetadata = $customRegionMetadata;
  }
  /**
   * @return CustomRegionMetadata[]
   */
  public function getCustomRegionMetadata()
  {
    return $this->customRegionMetadata;
  }
  /**
   * @param MultiRegionMetadata
   */
  public function setMultiRegionMetadata(MultiRegionMetadata $multiRegionMetadata)
  {
    $this->multiRegionMetadata = $multiRegionMetadata;
  }
  /**
   * @return MultiRegionMetadata
   */
  public function getMultiRegionMetadata()
  {
    return $this->multiRegionMetadata;
  }
  /**
   * @param HiveMetastoreVersion[]
   */
  public function setSupportedHiveMetastoreVersions($supportedHiveMetastoreVersions)
  {
    $this->supportedHiveMetastoreVersions = $supportedHiveMetastoreVersions;
  }
  /**
   * @return HiveMetastoreVersion[]
   */
  public function getSupportedHiveMetastoreVersions()
  {
    return $this->supportedHiveMetastoreVersions;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LocationMetadata::class, 'Google_Service_DataprocMetastore_LocationMetadata');
