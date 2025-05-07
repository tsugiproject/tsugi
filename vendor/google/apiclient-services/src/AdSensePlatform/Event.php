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

namespace Google\Service\AdSensePlatform;

class Event extends \Google\Model
{
  protected $eventInfoType = EventInfo::class;
  protected $eventInfoDataType = '';
  /**
   * @var string
   */
  public $eventTime;
  /**
   * @var string
   */
  public $eventType;

  /**
   * @param EventInfo
   */
  public function setEventInfo(EventInfo $eventInfo)
  {
    $this->eventInfo = $eventInfo;
  }
  /**
   * @return EventInfo
   */
  public function getEventInfo()
  {
    return $this->eventInfo;
  }
  /**
   * @param string
   */
  public function setEventTime($eventTime)
  {
    $this->eventTime = $eventTime;
  }
  /**
   * @return string
   */
  public function getEventTime()
  {
    return $this->eventTime;
  }
  /**
   * @param string
   */
  public function setEventType($eventType)
  {
    $this->eventType = $eventType;
  }
  /**
   * @return string
   */
  public function getEventType()
  {
    return $this->eventType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Event::class, 'Google_Service_AdSensePlatform_Event');
