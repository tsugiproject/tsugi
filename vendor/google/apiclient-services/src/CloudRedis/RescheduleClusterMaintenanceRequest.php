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

namespace Google\Service\CloudRedis;

class RescheduleClusterMaintenanceRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $rescheduleType;
  /**
   * @var string
   */
  public $scheduleTime;

  /**
   * @param string
   */
  public function setRescheduleType($rescheduleType)
  {
    $this->rescheduleType = $rescheduleType;
  }
  /**
   * @return string
   */
  public function getRescheduleType()
  {
    return $this->rescheduleType;
  }
  /**
   * @param string
   */
  public function setScheduleTime($scheduleTime)
  {
    $this->scheduleTime = $scheduleTime;
  }
  /**
   * @return string
   */
  public function getScheduleTime()
  {
    return $this->scheduleTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RescheduleClusterMaintenanceRequest::class, 'Google_Service_CloudRedis_RescheduleClusterMaintenanceRequest');
