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

namespace Google\Service\CloudDeploy;

class WeeklyWindow extends \Google\Collection
{
  protected $collection_key = 'daysOfWeek';
  /**
   * @var string[]
   */
  public $daysOfWeek;
  protected $endTimeType = TimeOfDay::class;
  protected $endTimeDataType = '';
  protected $startTimeType = TimeOfDay::class;
  protected $startTimeDataType = '';

  /**
   * @param string[]
   */
  public function setDaysOfWeek($daysOfWeek)
  {
    $this->daysOfWeek = $daysOfWeek;
  }
  /**
   * @return string[]
   */
  public function getDaysOfWeek()
  {
    return $this->daysOfWeek;
  }
  /**
   * @param TimeOfDay
   */
  public function setEndTime(TimeOfDay $endTime)
  {
    $this->endTime = $endTime;
  }
  /**
   * @return TimeOfDay
   */
  public function getEndTime()
  {
    return $this->endTime;
  }
  /**
   * @param TimeOfDay
   */
  public function setStartTime(TimeOfDay $startTime)
  {
    $this->startTime = $startTime;
  }
  /**
   * @return TimeOfDay
   */
  public function getStartTime()
  {
    return $this->startTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(WeeklyWindow::class, 'Google_Service_CloudDeploy_WeeklyWindow');
