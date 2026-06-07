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

namespace Google\Service\CloudNumberRegistry;

class Range extends \Google\Model
{
  protected $customRangeType = CustomRange::class;
  protected $customRangeDataType = '';
  protected $discoveredRangeType = DiscoveredRange::class;
  protected $discoveredRangeDataType = '';
  protected $utilizationType = RangeUtilization::class;
  protected $utilizationDataType = '';

  /**
   * A custom range.
   *
   * @param CustomRange $customRange
   */
  public function setCustomRange(CustomRange $customRange)
  {
    $this->customRange = $customRange;
  }
  /**
   * @return CustomRange
   */
  public function getCustomRange()
  {
    return $this->customRange;
  }
  /**
   * A discovered range.
   *
   * @param DiscoveredRange $discoveredRange
   */
  public function setDiscoveredRange(DiscoveredRange $discoveredRange)
  {
    $this->discoveredRange = $discoveredRange;
  }
  /**
   * @return DiscoveredRange
   */
  public function getDiscoveredRange()
  {
    return $this->discoveredRange;
  }
  /**
   * The utilization of the range.
   *
   * @param RangeUtilization $utilization
   */
  public function setUtilization(RangeUtilization $utilization)
  {
    $this->utilization = $utilization;
  }
  /**
   * @return RangeUtilization
   */
  public function getUtilization()
  {
    return $this->utilization;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Range::class, 'Google_Service_CloudNumberRegistry_Range');
