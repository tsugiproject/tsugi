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

namespace Google\Service\CloudRetail;

class GoogleCloudRetailV2LocalInventory extends \Google\Collection
{
  protected $collection_key = 'fulfillmentTypes';
  protected $attributesType = GoogleCloudRetailV2CustomAttribute::class;
  protected $attributesDataType = 'map';
  /**
   * @var string
   */
  public $availability;
  /**
   * @var string[]
   */
  public $fulfillmentTypes;
  /**
   * @var string
   */
  public $placeId;
  protected $priceInfoType = GoogleCloudRetailV2PriceInfo::class;
  protected $priceInfoDataType = '';

  /**
   * @param GoogleCloudRetailV2CustomAttribute[]
   */
  public function setAttributes($attributes)
  {
    $this->attributes = $attributes;
  }
  /**
   * @return GoogleCloudRetailV2CustomAttribute[]
   */
  public function getAttributes()
  {
    return $this->attributes;
  }
  /**
   * @param string
   */
  public function setAvailability($availability)
  {
    $this->availability = $availability;
  }
  /**
   * @return string
   */
  public function getAvailability()
  {
    return $this->availability;
  }
  /**
   * @param string[]
   */
  public function setFulfillmentTypes($fulfillmentTypes)
  {
    $this->fulfillmentTypes = $fulfillmentTypes;
  }
  /**
   * @return string[]
   */
  public function getFulfillmentTypes()
  {
    return $this->fulfillmentTypes;
  }
  /**
   * @param string
   */
  public function setPlaceId($placeId)
  {
    $this->placeId = $placeId;
  }
  /**
   * @return string
   */
  public function getPlaceId()
  {
    return $this->placeId;
  }
  /**
   * @param GoogleCloudRetailV2PriceInfo
   */
  public function setPriceInfo(GoogleCloudRetailV2PriceInfo $priceInfo)
  {
    $this->priceInfo = $priceInfo;
  }
  /**
   * @return GoogleCloudRetailV2PriceInfo
   */
  public function getPriceInfo()
  {
    return $this->priceInfo;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRetailV2LocalInventory::class, 'Google_Service_CloudRetail_GoogleCloudRetailV2LocalInventory');
