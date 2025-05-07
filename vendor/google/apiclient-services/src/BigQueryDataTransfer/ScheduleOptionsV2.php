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

namespace Google\Service\BigQueryDataTransfer;

class ScheduleOptionsV2 extends \Google\Model
{
  protected $eventDrivenScheduleType = EventDrivenSchedule::class;
  protected $eventDrivenScheduleDataType = '';
  protected $manualScheduleType = ManualSchedule::class;
  protected $manualScheduleDataType = '';
  protected $timeBasedScheduleType = TimeBasedSchedule::class;
  protected $timeBasedScheduleDataType = '';

  /**
   * @param EventDrivenSchedule
   */
  public function setEventDrivenSchedule(EventDrivenSchedule $eventDrivenSchedule)
  {
    $this->eventDrivenSchedule = $eventDrivenSchedule;
  }
  /**
   * @return EventDrivenSchedule
   */
  public function getEventDrivenSchedule()
  {
    return $this->eventDrivenSchedule;
  }
  /**
   * @param ManualSchedule
   */
  public function setManualSchedule(ManualSchedule $manualSchedule)
  {
    $this->manualSchedule = $manualSchedule;
  }
  /**
   * @return ManualSchedule
   */
  public function getManualSchedule()
  {
    return $this->manualSchedule;
  }
  /**
   * @param TimeBasedSchedule
   */
  public function setTimeBasedSchedule(TimeBasedSchedule $timeBasedSchedule)
  {
    $this->timeBasedSchedule = $timeBasedSchedule;
  }
  /**
   * @return TimeBasedSchedule
   */
  public function getTimeBasedSchedule()
  {
    return $this->timeBasedSchedule;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ScheduleOptionsV2::class, 'Google_Service_BigQueryDataTransfer_ScheduleOptionsV2');
