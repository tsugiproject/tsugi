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

class GoogleCloudDiscoveryengineV1alphaSearchRequestFacetSpecFacetKey extends \Google\Collection
{
  protected $collection_key = 'restrictedValues';
  /**
   * @var bool
   */
  public $caseInsensitive;
  /**
   * @var string[]
   */
  public $contains;
  protected $intervalsType = GoogleCloudDiscoveryengineV1alphaInterval::class;
  protected $intervalsDataType = 'array';
  /**
   * @var string
   */
  public $key;
  /**
   * @var string
   */
  public $orderBy;
  /**
   * @var string[]
   */
  public $prefixes;
  /**
   * @var string[]
   */
  public $restrictedValues;

  /**
   * @param bool
   */
  public function setCaseInsensitive($caseInsensitive)
  {
    $this->caseInsensitive = $caseInsensitive;
  }
  /**
   * @return bool
   */
  public function getCaseInsensitive()
  {
    return $this->caseInsensitive;
  }
  /**
   * @param string[]
   */
  public function setContains($contains)
  {
    $this->contains = $contains;
  }
  /**
   * @return string[]
   */
  public function getContains()
  {
    return $this->contains;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1alphaInterval[]
   */
  public function setIntervals($intervals)
  {
    $this->intervals = $intervals;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1alphaInterval[]
   */
  public function getIntervals()
  {
    return $this->intervals;
  }
  /**
   * @param string
   */
  public function setKey($key)
  {
    $this->key = $key;
  }
  /**
   * @return string
   */
  public function getKey()
  {
    return $this->key;
  }
  /**
   * @param string
   */
  public function setOrderBy($orderBy)
  {
    $this->orderBy = $orderBy;
  }
  /**
   * @return string
   */
  public function getOrderBy()
  {
    return $this->orderBy;
  }
  /**
   * @param string[]
   */
  public function setPrefixes($prefixes)
  {
    $this->prefixes = $prefixes;
  }
  /**
   * @return string[]
   */
  public function getPrefixes()
  {
    return $this->prefixes;
  }
  /**
   * @param string[]
   */
  public function setRestrictedValues($restrictedValues)
  {
    $this->restrictedValues = $restrictedValues;
  }
  /**
   * @return string[]
   */
  public function getRestrictedValues()
  {
    return $this->restrictedValues;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1alphaSearchRequestFacetSpecFacetKey::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1alphaSearchRequestFacetSpecFacetKey');
