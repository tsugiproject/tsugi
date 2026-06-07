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

namespace Google\Service\Config;

class DeploymentUnitProgress extends \Google\Model
{
  /**
   * Unspecified intent.
   */
  public const INTENT_INTENT_UNSPECIFIED = 'INTENT_UNSPECIFIED';
  /**
   * Create deployment in the unit from the deployment spec.
   */
  public const INTENT_CREATE_DEPLOYMENT = 'CREATE_DEPLOYMENT';
  /**
   * Update deployment in the unit.
   */
  public const INTENT_UPDATE_DEPLOYMENT = 'UPDATE_DEPLOYMENT';
  /**
   * Delete deployment in the unit.
   */
  public const INTENT_DELETE_DEPLOYMENT = 'DELETE_DEPLOYMENT';
  /**
   * Recreate deployment in the unit.
   */
  public const INTENT_RECREATE_DEPLOYMENT = 'RECREATE_DEPLOYMENT';
  /**
   * Delete deployment in latest successful revision while no longer referenced
   * in any deployment unit in the current deployment group.
   */
  public const INTENT_CLEAN_UP = 'CLEAN_UP';
  /**
   * Expected to be unchanged.
   */
  public const INTENT_UNCHANGED = 'UNCHANGED';
  /**
   * The default value. This value is unused.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The deployment unit is queued for deployment creation or update.
   */
  public const STATE_QUEUED = 'QUEUED';
  /**
   * The underlying deployment of the unit is being created or updated.
   */
  public const STATE_APPLYING_DEPLOYMENT = 'APPLYING_DEPLOYMENT';
  /**
   * The underlying deployment operation of the unit has succeeded.
   */
  public const STATE_SUCCEEDED = 'SUCCEEDED';
  /**
   * The underlying deployment operation of the unit has failed.
   */
  public const STATE_FAILED = 'FAILED';
  /**
   * The deployment unit was aborted, likely due to failures in other dependent
   * deployment units.
   */
  public const STATE_ABORTED = 'ABORTED';
  /**
   * The deployment unit was skipped because there were no changes to apply.
   */
  public const STATE_SKIPPED = 'SKIPPED';
  /**
   * The deployment is being deleted.
   */
  public const STATE_DELETING_DEPLOYMENT = 'DELETING_DEPLOYMENT';
  /**
   * The deployment is being previewed.
   */
  public const STATE_PREVIEWING_DEPLOYMENT = 'PREVIEWING_DEPLOYMENT';
  /**
   * Output only. The name of the deployment to be provisioned. Format:
   * 'projects/{project}/locations/{location}/deployments/{deployment}'.
   *
   * @var string
   */
  public $deployment;
  protected $deploymentOperationSummaryType = DeploymentOperationSummary::class;
  protected $deploymentOperationSummaryDataType = '';
  protected $errorType = Status::class;
  protected $errorDataType = '';
  /**
   * Output only. The intent of the deployment unit.
   *
   * @var string
   */
  public $intent;
  /**
   * Output only. The current step of the deployment unit provisioning.
   *
   * @var string
   */
  public $state;
  /**
   * Output only. Additional information regarding the current state.
   *
   * @var string
   */
  public $stateDescription;
  /**
   * Output only. The unit id of the deployment unit to be provisioned.
   *
   * @var string
   */
  public $unitId;

  /**
   * Output only. The name of the deployment to be provisioned. Format:
   * 'projects/{project}/locations/{location}/deployments/{deployment}'.
   *
   * @param string $deployment
   */
  public function setDeployment($deployment)
  {
    $this->deployment = $deployment;
  }
  /**
   * @return string
   */
  public function getDeployment()
  {
    return $this->deployment;
  }
  /**
   * Output only. The summary of the deployment operation.
   *
   * @param DeploymentOperationSummary $deploymentOperationSummary
   */
  public function setDeploymentOperationSummary(DeploymentOperationSummary $deploymentOperationSummary)
  {
    $this->deploymentOperationSummary = $deploymentOperationSummary;
  }
  /**
   * @return DeploymentOperationSummary
   */
  public function getDeploymentOperationSummary()
  {
    return $this->deploymentOperationSummary;
  }
  /**
   * Output only. Holds the error status of the deployment unit provisioning.
   *
   * @param Status $error
   */
  public function setError(Status $error)
  {
    $this->error = $error;
  }
  /**
   * @return Status
   */
  public function getError()
  {
    return $this->error;
  }
  /**
   * Output only. The intent of the deployment unit.
   *
   * Accepted values: INTENT_UNSPECIFIED, CREATE_DEPLOYMENT, UPDATE_DEPLOYMENT,
   * DELETE_DEPLOYMENT, RECREATE_DEPLOYMENT, CLEAN_UP, UNCHANGED
   *
   * @param self::INTENT_* $intent
   */
  public function setIntent($intent)
  {
    $this->intent = $intent;
  }
  /**
   * @return self::INTENT_*
   */
  public function getIntent()
  {
    return $this->intent;
  }
  /**
   * Output only. The current step of the deployment unit provisioning.
   *
   * Accepted values: STATE_UNSPECIFIED, QUEUED, APPLYING_DEPLOYMENT, SUCCEEDED,
   * FAILED, ABORTED, SKIPPED, DELETING_DEPLOYMENT, PREVIEWING_DEPLOYMENT
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * Output only. Additional information regarding the current state.
   *
   * @param string $stateDescription
   */
  public function setStateDescription($stateDescription)
  {
    $this->stateDescription = $stateDescription;
  }
  /**
   * @return string
   */
  public function getStateDescription()
  {
    return $this->stateDescription;
  }
  /**
   * Output only. The unit id of the deployment unit to be provisioned.
   *
   * @param string $unitId
   */
  public function setUnitId($unitId)
  {
    $this->unitId = $unitId;
  }
  /**
   * @return string
   */
  public function getUnitId()
  {
    return $this->unitId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeploymentUnitProgress::class, 'Google_Service_Config_DeploymentUnitProgress');
