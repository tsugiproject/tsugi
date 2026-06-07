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

class Events extends \Google\Collection
{
  protected $collection_key = 'events';
  /**
   * Optional. The ID of the provider component if the events are associated
   * with a specific component. Optional for WHDM events, required for UDDM
   * events.
   *
   * @var string
   */
  public $componentId;
  protected $eventsType = EventData::class;
  protected $eventsDataType = 'array';

  /**
   * Optional. The ID of the provider component if the events are associated
   * with a specific component. Optional for WHDM events, required for UDDM
   * events.
   *
   * @param string $componentId
   */
  public function setComponentId($componentId)
  {
    $this->componentId = $componentId;
  }
  /**
   * @return string
   */
  public function getComponentId()
  {
    return $this->componentId;
  }
  /**
   * Required. List of events associated with the component.
   *
   * @param EventData[] $events
   */
  public function setEvents($events)
  {
    $this->events = $events;
  }
  /**
   * @return EventData[]
   */
  public function getEvents()
  {
    return $this->events;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Events::class, 'Google_Service_HomeGraphService_Events');
