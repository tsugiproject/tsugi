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

namespace Google\Service\HomeGraphService;

class EventData extends \Google\Model
{
  /**
   * Required. The actual event payload.
   *
   * @var array[]
   */
  public $event;
  /**
   * Required. The unique event ID from the device provider.
   *
   * @var string
   */
  public $eventId;
  /**
   * Required. The timestamp of the event.
   *
   * @var string
   */
  public $eventTime;

  /**
   * Required. The actual event payload.
   *
   * @param array[] $event
   */
  public function setEvent($event)
  {
    $this->event = $event;
  }
  /**
   * @return array[]
   */
  public function getEvent()
  {
    return $this->event;
  }
  /**
   * Required. The unique event ID from the device provider.
   *
   * @param string $eventId
   */
  public function setEventId($eventId)
  {
    $this->eventId = $eventId;
  }
  /**
   * @return string
   */
  public function getEventId()
  {
    return $this->eventId;
  }
  /**
   * Required. The timestamp of the event.
   *
   * @param string $eventTime
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EventData::class, 'Google_Service_HomeGraphService_EventData');
