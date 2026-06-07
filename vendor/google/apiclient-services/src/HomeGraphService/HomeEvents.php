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

class HomeEvents extends \Google\Collection
{
  protected $collection_key = 'events';
  /**
   * Required. / Unique identifier for the device.
   *
   * @var string
   */
  public $deviceId;
  protected $eventsType = Events::class;
  protected $eventsDataType = 'array';

  /**
   * Required. / Unique identifier for the device.
   *
   * @param string $deviceId
   */
  public function setDeviceId($deviceId)
  {
    $this->deviceId = $deviceId;
  }
  /**
   * @return string
   */
  public function getDeviceId()
  {
    return $this->deviceId;
  }
  /**
   * Required. List of events for the item.
   *
   * @param Events[] $events
   */
  public function setEvents($events)
  {
    $this->events = $events;
  }
  /**
   * @return Events[]
   */
  public function getEvents()
  {
    return $this->events;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HomeEvents::class, 'Google_Service_HomeGraphService_HomeEvents');
