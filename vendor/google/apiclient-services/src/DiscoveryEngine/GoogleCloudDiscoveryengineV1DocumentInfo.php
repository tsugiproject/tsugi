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

class GoogleCloudDiscoveryengineV1DocumentInfo extends \Google\Collection
{
  protected $collection_key = 'promotionIds';
  /**
   * @var float
   */
  public $conversionValue;
  /**
   * @var string
   */
  public $id;
  /**
   * @var bool
   */
  public $joined;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string[]
   */
  public $promotionIds;
  /**
   * @var int
   */
  public $quantity;
  /**
   * @var string
   */
  public $uri;

  /**
   * @param float
   */
  public function setConversionValue($conversionValue)
  {
    $this->conversionValue = $conversionValue;
  }
  /**
   * @return float
   */
  public function getConversionValue()
  {
    return $this->conversionValue;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param bool
   */
  public function setJoined($joined)
  {
    $this->joined = $joined;
  }
  /**
   * @return bool
   */
  public function getJoined()
  {
    return $this->joined;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param string[]
   */
  public function setPromotionIds($promotionIds)
  {
    $this->promotionIds = $promotionIds;
  }
  /**
   * @return string[]
   */
  public function getPromotionIds()
  {
    return $this->promotionIds;
  }
  /**
   * @param int
   */
  public function setQuantity($quantity)
  {
    $this->quantity = $quantity;
  }
  /**
   * @return int
   */
  public function getQuantity()
  {
    return $this->quantity;
  }
  /**
   * @param string
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1DocumentInfo::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1DocumentInfo');
