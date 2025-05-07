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

namespace Google\Service\SecurityCommandCenter;

class DataFlowEvent extends \Google\Model
{
  /**
   * @var string
   */
  public $eventId;
  /**
   * @var string
   */
  public $eventTime;
  /**
   * @var string
   */
  public $operation;
  /**
   * @var string
   */
  public $principalEmail;
  /**
   * @var string
   */
  public $violatedLocation;

  /**
   * @param string
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
  public function setOperation($operation)
  {
    $this->operation = $operation;
  }
  /**
   * @return string
   */
  public function getOperation()
  {
    return $this->operation;
  }
  /**
   * @param string
   */
  public function setPrincipalEmail($principalEmail)
  {
    $this->principalEmail = $principalEmail;
  }
  /**
   * @return string
   */
  public function getPrincipalEmail()
  {
    return $this->principalEmail;
  }
  /**
   * @param string
   */
  public function setViolatedLocation($violatedLocation)
  {
    $this->violatedLocation = $violatedLocation;
  }
  /**
   * @return string
   */
  public function getViolatedLocation()
  {
    return $this->violatedLocation;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DataFlowEvent::class, 'Google_Service_SecurityCommandCenter_DataFlowEvent');
