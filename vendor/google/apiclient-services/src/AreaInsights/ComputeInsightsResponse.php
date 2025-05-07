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

namespace Google\Service\AreaInsights;

class ComputeInsightsResponse extends \Google\Collection
{
  protected $collection_key = 'placeInsights';
  /**
   * @var string
   */
  public $count;
  protected $placeInsightsType = PlaceInsight::class;
  protected $placeInsightsDataType = 'array';

  /**
   * @param string
   */
  public function setCount($count)
  {
    $this->count = $count;
  }
  /**
   * @return string
   */
  public function getCount()
  {
    return $this->count;
  }
  /**
   * @param PlaceInsight[]
   */
  public function setPlaceInsights($placeInsights)
  {
    $this->placeInsights = $placeInsights;
  }
  /**
   * @return PlaceInsight[]
   */
  public function getPlaceInsights()
  {
    return $this->placeInsights;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ComputeInsightsResponse::class, 'Google_Service_AreaInsights_ComputeInsightsResponse');
