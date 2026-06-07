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

class RangeUtilization extends \Google\Model
{
  /**
   * Output only. The total number of IP addresses consumed in the range.
   *
   * @var string
   */
  public $totalConsumed;
  /**
   * Output only. The total number of IP addresses produced in the range.
   *
   * @var string
   */
  public $totalProduced;
  /**
   * Output only. The usage of the range as a percentage. This is marked as
   * optional so that we have presence tracking and API responses show 0.0
   * instead of NULL.
   *
   * @var 
   */
  public $usage;

  /**
   * Output only. The total number of IP addresses consumed in the range.
   *
   * @param string $totalConsumed
   */
  public function setTotalConsumed($totalConsumed)
  {
    $this->totalConsumed = $totalConsumed;
  }
  /**
   * @return string
   */
  public function getTotalConsumed()
  {
    return $this->totalConsumed;
  }
  /**
   * Output only. The total number of IP addresses produced in the range.
   *
   * @param string $totalProduced
   */
  public function setTotalProduced($totalProduced)
  {
    $this->totalProduced = $totalProduced;
  }
  /**
   * @return string
   */
  public function getTotalProduced()
  {
    return $this->totalProduced;
  }
  public function setUsage($usage)
  {
    $this->usage = $usage;
  }
  public function getUsage()
  {
    return $this->usage;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RangeUtilization::class, 'Google_Service_CloudNumberRegistry_RangeUtilization');
