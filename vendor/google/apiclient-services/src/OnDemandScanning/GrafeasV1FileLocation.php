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

namespace Google\Service\OnDemandScanning;

class GrafeasV1FileLocation extends \Google\Model
{
  /**
   * @var string
   */
  public $filePath;
  protected $layerDetailsType = GrafeasV1LayerDetails::class;
  protected $layerDetailsDataType = '';

  /**
   * @param string
   */
  public function setFilePath($filePath)
  {
    $this->filePath = $filePath;
  }
  /**
   * @return string
   */
  public function getFilePath()
  {
    return $this->filePath;
  }
  /**
   * @param GrafeasV1LayerDetails
   */
  public function setLayerDetails(GrafeasV1LayerDetails $layerDetails)
  {
    $this->layerDetails = $layerDetails;
  }
  /**
   * @return GrafeasV1LayerDetails
   */
  public function getLayerDetails()
  {
    return $this->layerDetails;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GrafeasV1FileLocation::class, 'Google_Service_OnDemandScanning_GrafeasV1FileLocation');
