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

namespace Google\Service\CustomerEngagementSuite;

class EvaluationMetricsThresholdsToolMatchingSettings extends \Google\Model
{
  /**
   * Unspecified behavior. Defaults to FAIL.
   */
  public const EXTRA_TOOL_CALL_BEHAVIOR_EXTRA_TOOL_CALL_BEHAVIOR_UNSPECIFIED = 'EXTRA_TOOL_CALL_BEHAVIOR_UNSPECIFIED';
  /**
   * Fail the evaluation if an extra tool call is encountered.
   */
  public const EXTRA_TOOL_CALL_BEHAVIOR_FAIL = 'FAIL';
  /**
   * Allow the extra tool call.
   */
  public const EXTRA_TOOL_CALL_BEHAVIOR_ALLOW = 'ALLOW';
  /**
   * Optional. Behavior for extra tool calls. Defaults to FAIL.
   *
   * @var string
   */
  public $extraToolCallBehavior;

  /**
   * Optional. Behavior for extra tool calls. Defaults to FAIL.
   *
   * Accepted values: EXTRA_TOOL_CALL_BEHAVIOR_UNSPECIFIED, FAIL, ALLOW
   *
   * @param self::EXTRA_TOOL_CALL_BEHAVIOR_* $extraToolCallBehavior
   */
  public function setExtraToolCallBehavior($extraToolCallBehavior)
  {
    $this->extraToolCallBehavior = $extraToolCallBehavior;
  }
  /**
   * @return self::EXTRA_TOOL_CALL_BEHAVIOR_*
   */
  public function getExtraToolCallBehavior()
  {
    return $this->extraToolCallBehavior;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EvaluationMetricsThresholdsToolMatchingSettings::class, 'Google_Service_CustomerEngagementSuite_EvaluationMetricsThresholdsToolMatchingSettings');
