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

namespace Google\Service\Container;

class RecurringMaintenanceWindow extends \Google\Model
{
  protected $delayUntilType = Date::class;
  protected $delayUntilDataType = '';
  /**
   * Required. An RRULE (https://tools.ietf.org/html/rfc5545#section-3.8.5.3)
   * for how this window recurs. For example, to have something repeat every
   * weekday, you'd use: `FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR` To repeat some
   * window daily (equivalent to the DailyMaintenanceWindow): `FREQ=DAILY` For
   * the first weekend of every month: `FREQ=MONTHLY;BYSETPOS=1;BYDAY=SA,SU` The
   * FREQ values of HOURLY, MINUTELY, and SECONDLY are not supported.
   *
   * @var string
   */
  public $recurrence;
  /**
   * Required. Duration of the window.
   *
   * @var string
   */
  public $windowDuration;
  protected $windowStartTimeType = TimeOfDay::class;
  protected $windowStartTimeDataType = '';

  /**
   * Optional. Specifies the date before which will not be scheduled. Depending
   * on the recurrence, this may be the date the first window appears. Days are
   * measured in the UTC timezone. This setting must be used when INTERVAL>1 or
   * FREQ=WEEKLY/MONTHLY and no BYDAY specified.
   *
   * @param Date $delayUntil
   */
  public function setDelayUntil(Date $delayUntil)
  {
    $this->delayUntil = $delayUntil;
  }
  /**
   * @return Date
   */
  public function getDelayUntil()
  {
    return $this->delayUntil;
  }
  /**
   * Required. An RRULE (https://tools.ietf.org/html/rfc5545#section-3.8.5.3)
   * for how this window recurs. For example, to have something repeat every
   * weekday, you'd use: `FREQ=WEEKLY;BYDAY=MO,TU,WE,TH,FR` To repeat some
   * window daily (equivalent to the DailyMaintenanceWindow): `FREQ=DAILY` For
   * the first weekend of every month: `FREQ=MONTHLY;BYSETPOS=1;BYDAY=SA,SU` The
   * FREQ values of HOURLY, MINUTELY, and SECONDLY are not supported.
   *
   * @param string $recurrence
   */
  public function setRecurrence($recurrence)
  {
    $this->recurrence = $recurrence;
  }
  /**
   * @return string
   */
  public function getRecurrence()
  {
    return $this->recurrence;
  }
  /**
   * Required. Duration of the window.
   *
   * @param string $windowDuration
   */
  public function setWindowDuration($windowDuration)
  {
    $this->windowDuration = $windowDuration;
  }
  /**
   * @return string
   */
  public function getWindowDuration()
  {
    return $this->windowDuration;
  }
  /**
   * Required. Start time of the window on days that it is scheduled, assuming
   * UTC timezone.
   *
   * @param TimeOfDay $windowStartTime
   */
  public function setWindowStartTime(TimeOfDay $windowStartTime)
  {
    $this->windowStartTime = $windowStartTime;
  }
  /**
   * @return TimeOfDay
   */
  public function getWindowStartTime()
  {
    return $this->windowStartTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RecurringMaintenanceWindow::class, 'Google_Service_Container_RecurringMaintenanceWindow');
