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

class AutomationRule extends \Google\Model
{
  protected $advanceRolloutRuleType = AdvanceRolloutRule::class;
  protected $advanceRolloutRuleDataType = '';
  protected $promoteReleaseRuleType = PromoteReleaseRule::class;
  protected $promoteReleaseRuleDataType = '';
  protected $repairRolloutRuleType = RepairRolloutRule::class;
  protected $repairRolloutRuleDataType = '';
  protected $timedPromoteReleaseRuleType = TimedPromoteReleaseRule::class;
  protected $timedPromoteReleaseRuleDataType = '';

  /**
   * @param AdvanceRolloutRule
   */
  public function setAdvanceRolloutRule(AdvanceRolloutRule $advanceRolloutRule)
  {
    $this->advanceRolloutRule = $advanceRolloutRule;
  }
  /**
   * @return AdvanceRolloutRule
   */
  public function getAdvanceRolloutRule()
  {
    return $this->advanceRolloutRule;
  }
  /**
   * @param PromoteReleaseRule
   */
  public function setPromoteReleaseRule(PromoteReleaseRule $promoteReleaseRule)
  {
    $this->promoteReleaseRule = $promoteReleaseRule;
  }
  /**
   * @return PromoteReleaseRule
   */
  public function getPromoteReleaseRule()
  {
    return $this->promoteReleaseRule;
  }
  /**
   * @param RepairRolloutRule
   */
  public function setRepairRolloutRule(RepairRolloutRule $repairRolloutRule)
  {
    $this->repairRolloutRule = $repairRolloutRule;
  }
  /**
   * @return RepairRolloutRule
   */
  public function getRepairRolloutRule()
  {
    return $this->repairRolloutRule;
  }
  /**
   * @param TimedPromoteReleaseRule
   */
  public function setTimedPromoteReleaseRule(TimedPromoteReleaseRule $timedPromoteReleaseRule)
  {
    $this->timedPromoteReleaseRule = $timedPromoteReleaseRule;
  }
  /**
   * @return TimedPromoteReleaseRule
   */
  public function getTimedPromoteReleaseRule()
  {
    return $this->timedPromoteReleaseRule;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AutomationRule::class, 'Google_Service_CloudDeploy_AutomationRule');
