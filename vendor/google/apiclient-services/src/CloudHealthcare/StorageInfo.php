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

namespace Google\Service\CloudHealthcare;

class StorageInfo extends \Google\Model
{
  protected $blobStorageInfoType = BlobStorageInfo::class;
  protected $blobStorageInfoDataType = '';
  /**
   * @var string
   */
  public $referencedResource;
  protected $structuredStorageInfoType = StructuredStorageInfo::class;
  protected $structuredStorageInfoDataType = '';

  /**
   * @param BlobStorageInfo
   */
  public function setBlobStorageInfo(BlobStorageInfo $blobStorageInfo)
  {
    $this->blobStorageInfo = $blobStorageInfo;
  }
  /**
   * @return BlobStorageInfo
   */
  public function getBlobStorageInfo()
  {
    return $this->blobStorageInfo;
  }
  /**
   * @param string
   */
  public function setReferencedResource($referencedResource)
  {
    $this->referencedResource = $referencedResource;
  }
  /**
   * @return string
   */
  public function getReferencedResource()
  {
    return $this->referencedResource;
  }
  /**
   * @param StructuredStorageInfo
   */
  public function setStructuredStorageInfo(StructuredStorageInfo $structuredStorageInfo)
  {
    $this->structuredStorageInfo = $structuredStorageInfo;
  }
  /**
   * @return StructuredStorageInfo
   */
  public function getStructuredStorageInfo()
  {
    return $this->structuredStorageInfo;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(StorageInfo::class, 'Google_Service_CloudHealthcare_StorageInfo');
