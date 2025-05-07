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

class TimedPromoteReleaseRule extends \Google\Model
{
  protected $conditionType = AutomationRuleCondition::class;
  protected $conditionDataType = '';
  /**
   * @var string
   */
  public $destinationPhase;
  /**
   * @var string
   */
  public $destinationTargetId;
  /**
   * @var string
   */
  public $id;
  /**
   * @var string
   */
  public $schedule;
  /**
   * @var string
   */
  public $timeZone;

  /**
   * @param AutomationRuleCondition
   */
  public function setCondition(AutomationRuleCondition $condition)
  {
    $this->condition = $condition;
  }
  /**
   * @return AutomationRuleCondition
   */
  public function getCondition()
  {
    return $this->condition;
  }
  /**
   * @param string
   */
  public function setDestinationPhase($destinationPhase)
  {
    $this->destinationPhase = $destinationPhase;
  }
  /**
   * @return string
   */
  public function getDestinationPhase()
  {
    return $this->destinationPhase;
  }
  /**
   * @param string
   */
  public function setDestinationTargetId($destinationTargetId)
  {
    $this->destinationTargetId = $destinationTargetId;
  }
  /**
   * @return string
   */
  public function getDestinationTargetId()
  {
    return $this->destinationTargetId;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param string
   */
  public function setSchedule($schedule)
  {
    $this->schedule = $schedule;
  }
  /**
   * @return string
   */
  public function getSchedule()
  {
    return $this->schedule;
  }
  /**
   * @param string
   */
  public function setTimeZone($timeZone)
  {
    $this->timeZone = $timeZone;
  }
  /**
   * @return string
   */
  public function getTimeZone()
  {
    return $this->timeZone;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TimedPromoteReleaseRule::class, 'Google_Service_CloudDeploy_TimedPromoteReleaseRule');
