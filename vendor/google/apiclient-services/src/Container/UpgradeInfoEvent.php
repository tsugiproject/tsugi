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

class UpgradeInfoEvent extends \Google\Model
{
  /**
   * @var string
   */
  public $currentVersion;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $endTime;
  /**
   * @var string
   */
  public $eventType;
  /**
   * @var string
   */
  public $extendedSupportEndTime;
  /**
   * @var string
   */
  public $operation;
  /**
   * @var string
   */
  public $resource;
  /**
   * @var string
   */
  public $resourceType;
  /**
   * @var string
   */
  public $standardSupportEndTime;
  /**
   * @var string
   */
  public $startTime;
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $targetVersion;

  /**
   * @param string
   */
  public function setCurrentVersion($currentVersion)
  {
    $this->currentVersion = $currentVersion;
  }
  /**
   * @return string
   */
  public function getCurrentVersion()
  {
    return $this->currentVersion;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param string
   */
  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  /**
   * @return string
   */
  public function getEndTime()
  {
    return $this->endTime;
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
  /**
   * @param string
   */
  public function setExtendedSupportEndTime($extendedSupportEndTime)
  {
    $this->extendedSupportEndTime = $extendedSupportEndTime;
  }
  /**
   * @return string
   */
  public function getExtendedSupportEndTime()
  {
    return $this->extendedSupportEndTime;
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
  public function setResource($resource)
  {
    $this->resource = $resource;
  }
  /**
   * @return string
   */
  public function getResource()
  {
    return $this->resource;
  }
  /**
   * @param string
   */
  public function setResourceType($resourceType)
  {
    $this->resourceType = $resourceType;
  }
  /**
   * @return string
   */
  public function getResourceType()
  {
    return $this->resourceType;
  }
  /**
   * @param string
   */
  public function setStandardSupportEndTime($standardSupportEndTime)
  {
    $this->standardSupportEndTime = $standardSupportEndTime;
  }
  /**
   * @return string
   */
  public function getStandardSupportEndTime()
  {
    return $this->standardSupportEndTime;
  }
  /**
   * @param string
   */
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  /**
   * @return string
   */
  public function getStartTime()
  {
    return $this->startTime;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * @param string
   */
  public function setTargetVersion($targetVersion)
  {
    $this->targetVersion = $targetVersion;
  }
  /**
   * @return string
   */
  public function getTargetVersion()
  {
    return $this->targetVersion;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(UpgradeInfoEvent::class, 'Google_Service_Container_UpgradeInfoEvent');
