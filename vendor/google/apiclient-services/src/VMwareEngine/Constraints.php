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

namespace Google\Service\VMwareEngine;

class Constraints extends \Google\Model
{
  /**
   * @var int
   */
  public $minHoursDay;
  /**
   * @var int
   */
  public $minHoursWeek;
  protected $rescheduleDateRangeType = Interval::class;
  protected $rescheduleDateRangeDataType = '';

  /**
   * @param int
   */
  public function setMinHoursDay($minHoursDay)
  {
    $this->minHoursDay = $minHoursDay;
  }
  /**
   * @return int
   */
  public function getMinHoursDay()
  {
    return $this->minHoursDay;
  }
  /**
   * @param int
   */
  public function setMinHoursWeek($minHoursWeek)
  {
    $this->minHoursWeek = $minHoursWeek;
  }
  /**
   * @return int
   */
  public function getMinHoursWeek()
  {
    return $this->minHoursWeek;
  }
  /**
   * @param Interval
   */
  public function setRescheduleDateRange(Interval $rescheduleDateRange)
  {
    $this->rescheduleDateRange = $rescheduleDateRange;
  }
  /**
   * @return Interval
   */
  public function getRescheduleDateRange()
  {
    return $this->rescheduleDateRange;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Constraints::class, 'Google_Service_VMwareEngine_Constraints');
