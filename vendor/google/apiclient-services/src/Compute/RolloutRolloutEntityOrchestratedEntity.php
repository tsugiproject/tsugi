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

namespace Google\Service\Compute;

class RolloutRolloutEntityOrchestratedEntity extends \Google\Model
{
  /**
   * Required. Specifies the behavior of the Rollout if an out of band update is
   * detected in a project during a Rollout. It can be one of the following
   * values: 1) overwrite : Overwrite the local value with the rollout value. 2)
   * no_overwrite : Do not overwrite the local value with the rollout value.
   *
   * @var string
   */
  public $conflictBehavior;
  /**
   * Required. Orchestration action during the Rollout. It can be one of the
   * following values: 1) "update": Resources will be updated by the rollout. 2)
   * "delete": Resources will be deleted by the rollout.
   *
   * @var string
   */
  public $orchestrationAction;
  /**
   * Required. Fully qualified resource name of the resource which contains the
   * source of truth of the configuration being rolled out across
   * locations/projects. For example, in the case of a global Rollout which is
   * applied across regions, this contains the name of the global resource
   * created by the user which contains a payload for a resource that is
   * orchestrated across regions. This follows the following format:
   * //.googleapis.com/projects//locations/global// e.g. //osconfig.googleapis.c
   * om/projects/1/locations/global/policyOrchestrators/po1
   *
   * @var string
   */
  public $orchestrationSource;

  /**
   * Required. Specifies the behavior of the Rollout if an out of band update is
   * detected in a project during a Rollout. It can be one of the following
   * values: 1) overwrite : Overwrite the local value with the rollout value. 2)
   * no_overwrite : Do not overwrite the local value with the rollout value.
   *
   * @param string $conflictBehavior
   */
  public function setConflictBehavior($conflictBehavior)
  {
    $this->conflictBehavior = $conflictBehavior;
  }
  /**
   * @return string
   */
  public function getConflictBehavior()
  {
    return $this->conflictBehavior;
  }
  /**
   * Required. Orchestration action during the Rollout. It can be one of the
   * following values: 1) "update": Resources will be updated by the rollout. 2)
   * "delete": Resources will be deleted by the rollout.
   *
   * @param string $orchestrationAction
   */
  public function setOrchestrationAction($orchestrationAction)
  {
    $this->orchestrationAction = $orchestrationAction;
  }
  /**
   * @return string
   */
  public function getOrchestrationAction()
  {
    return $this->orchestrationAction;
  }
  /**
   * Required. Fully qualified resource name of the resource which contains the
   * source of truth of the configuration being rolled out across
   * locations/projects. For example, in the case of a global Rollout which is
   * applied across regions, this contains the name of the global resource
   * created by the user which contains a payload for a resource that is
   * orchestrated across regions. This follows the following format:
   * //.googleapis.com/projects//locations/global// e.g. //osconfig.googleapis.c
   * om/projects/1/locations/global/policyOrchestrators/po1
   *
   * @param string $orchestrationSource
   */
  public function setOrchestrationSource($orchestrationSource)
  {
    $this->orchestrationSource = $orchestrationSource;
  }
  /**
   * @return string
   */
  public function getOrchestrationSource()
  {
    return $this->orchestrationSource;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutRolloutEntityOrchestratedEntity::class, 'Google_Service_Compute_RolloutRolloutEntityOrchestratedEntity');
