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

class ProvisionDeploymentGroupOperationMetadata extends \Google\Collection
{
  /**
   * Unspecified step.
   */
  public const STEP_PROVISION_DEPLOYMENT_GROUP_STEP_UNSPECIFIED = 'PROVISION_DEPLOYMENT_GROUP_STEP_UNSPECIFIED';
  /**
   * Validating the deployment group.
   */
  public const STEP_VALIDATING_DEPLOYMENT_GROUP = 'VALIDATING_DEPLOYMENT_GROUP';
  /**
   * Locking the deployments to the deployment group for atomic actuation.
   */
  public const STEP_ASSOCIATING_DEPLOYMENTS_TO_DEPLOYMENT_GROUP = 'ASSOCIATING_DEPLOYMENTS_TO_DEPLOYMENT_GROUP';
  /**
   * Provisioning the deployment units.
   */
  public const STEP_PROVISIONING_DEPLOYMENT_UNITS = 'PROVISIONING_DEPLOYMENT_UNITS';
  /**
   * Unlocking the deployments from the deployment group after actuation.
   */
  public const STEP_DISASSOCIATING_DEPLOYMENTS_FROM_DEPLOYMENT_GROUP = 'DISASSOCIATING_DEPLOYMENTS_FROM_DEPLOYMENT_GROUP';
  /**
   * The operation has succeeded.
   */
  public const STEP_SUCCEEDED = 'SUCCEEDED';
  /**
   * The operation has failed.
   */
  public const STEP_FAILED = 'FAILED';
  /**
   * Deprovisioning the deployment units.
   */
  public const STEP_DEPROVISIONING_DEPLOYMENT_UNITS = 'DEPROVISIONING_DEPLOYMENT_UNITS';
  protected $collection_key = 'deploymentUnitProgresses';
  protected $deploymentUnitProgressesType = DeploymentUnitProgress::class;
  protected $deploymentUnitProgressesDataType = 'array';
  /**
   * Output only. The current step of the deployment group operation.
   *
   * @var string
   */
  public $step;

  /**
   * Output only. Progress information for each deployment unit within the
   * operation.
   *
   * @param DeploymentUnitProgress[] $deploymentUnitProgresses
   */
  public function setDeploymentUnitProgresses($deploymentUnitProgresses)
  {
    $this->deploymentUnitProgresses = $deploymentUnitProgresses;
  }
  /**
   * @return DeploymentUnitProgress[]
   */
  public function getDeploymentUnitProgresses()
  {
    return $this->deploymentUnitProgresses;
  }
  /**
   * Output only. The current step of the deployment group operation.
   *
   * Accepted values: PROVISION_DEPLOYMENT_GROUP_STEP_UNSPECIFIED,
   * VALIDATING_DEPLOYMENT_GROUP, ASSOCIATING_DEPLOYMENTS_TO_DEPLOYMENT_GROUP,
   * PROVISIONING_DEPLOYMENT_UNITS,
   * DISASSOCIATING_DEPLOYMENTS_FROM_DEPLOYMENT_GROUP, SUCCEEDED, FAILED,
   * DEPROVISIONING_DEPLOYMENT_UNITS
   *
   * @param self::STEP_* $step
   */
  public function setStep($step)
  {
    $this->step = $step;
  }
  /**
   * @return self::STEP_*
   */
  public function getStep()
  {
    return $this->step;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProvisionDeploymentGroupOperationMetadata::class, 'Google_Service_Config_ProvisionDeploymentGroupOperationMetadata');
