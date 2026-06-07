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

class DeploymentGroup extends \Google\Collection
{
  /**
   * Unspecified provisioning state.
   */
  public const PROVISIONING_STATE_PROVISIONING_STATE_UNSPECIFIED = 'PROVISIONING_STATE_UNSPECIFIED';
  /**
   * The deployment group is being provisioned.
   */
  public const PROVISIONING_STATE_PROVISIONING = 'PROVISIONING';
  /**
   * The deployment group is provisioned.
   */
  public const PROVISIONING_STATE_PROVISIONED = 'PROVISIONED';
  /**
   * The deployment group failed to be provisioned.
   */
  public const PROVISIONING_STATE_FAILED_TO_PROVISION = 'FAILED_TO_PROVISION';
  /**
   * The deployment group is being deprovisioned.
   */
  public const PROVISIONING_STATE_DEPROVISIONING = 'DEPROVISIONING';
  /**
   * The deployment group is deprovisioned.
   */
  public const PROVISIONING_STATE_DEPROVISIONED = 'DEPROVISIONED';
  /**
   * The deployment group failed to be deprovisioned.
   */
  public const PROVISIONING_STATE_FAILED_TO_DEPROVISION = 'FAILED_TO_DEPROVISION';
  /**
   * The default value. This value is used if the state is omitted.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The deployment group is being created.
   */
  public const STATE_CREATING = 'CREATING';
  /**
   * The deployment group is healthy.
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * The deployment group is being updated.
   */
  public const STATE_UPDATING = 'UPDATING';
  /**
   * The deployment group is being deleted.
   */
  public const STATE_DELETING = 'DELETING';
  /**
   * The deployment group has encountered an unexpected error.
   */
  public const STATE_FAILED = 'FAILED';
  /**
   * The deployment group is no longer being actively reconciled. This may be
   * the result of recovering the project after deletion.
   */
  public const STATE_SUSPENDED = 'SUSPENDED';
  /**
   * The deployment group has been deleted.
   */
  public const STATE_DELETED = 'DELETED';
  protected $collection_key = 'deploymentUnits';
  /**
   * Optional. Arbitrary key-value metadata storage e.g. to help client tools
   * identify deployment group during automation. See
   * https://google.aip.dev/148#annotations for details on format and size
   * limitations.
   *
   * @var string[]
   */
  public $annotations;
  /**
   * Output only. Time when the deployment group was created.
   *
   * @var string
   */
  public $createTime;
  protected $deploymentUnitsType = DeploymentUnit::class;
  protected $deploymentUnitsDataType = 'array';
  /**
   * Optional. User-defined metadata for the deployment group.
   *
   * @var string[]
   */
  public $labels;
  /**
   * Identifier. The name of the deployment group. Format: 'projects/{project_id
   * }/locations/{location}/deploymentGroups/{deployment_group}'.
   *
   * @var string
   */
  public $name;
  protected $provisioningErrorType = Status::class;
  protected $provisioningErrorDataType = '';
  /**
   * Output only. The provisioning state of the deployment group.
   *
   * @var string
   */
  public $provisioningState;
  /**
   * Output only. Additional information regarding the current provisioning
   * state.
   *
   * @var string
   */
  public $provisioningStateDescription;
  /**
   * Output only. Current state of the deployment group.
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
   * Output only. Time when the deployment group was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Optional. Arbitrary key-value metadata storage e.g. to help client tools
   * identify deployment group during automation. See
   * https://google.aip.dev/148#annotations for details on format and size
   * limitations.
   *
   * @param string[] $annotations
   */
  public function setAnnotations($annotations)
  {
    $this->annotations = $annotations;
  }
  /**
   * @return string[]
   */
  public function getAnnotations()
  {
    return $this->annotations;
  }
  /**
   * Output only. Time when the deployment group was created.
   *
   * @param string $createTime
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * The deployment units of the deployment group in a DAG like structure. When
   * a deployment group is being provisioned, the deployment units are deployed
   * in a DAG order. The provided units must be in a DAG order, otherwise an
   * error will be returned.
   *
   * @param DeploymentUnit[] $deploymentUnits
   */
  public function setDeploymentUnits($deploymentUnits)
  {
    $this->deploymentUnits = $deploymentUnits;
  }
  /**
   * @return DeploymentUnit[]
   */
  public function getDeploymentUnits()
  {
    return $this->deploymentUnits;
  }
  /**
   * Optional. User-defined metadata for the deployment group.
   *
   * @param string[] $labels
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * Identifier. The name of the deployment group. Format: 'projects/{project_id
   * }/locations/{location}/deploymentGroups/{deployment_group}'.
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Output only. The error status of the deployment group provisioning or
   * deprovisioning.
   *
   * @param Status $provisioningError
   */
  public function setProvisioningError(Status $provisioningError)
  {
    $this->provisioningError = $provisioningError;
  }
  /**
   * @return Status
   */
  public function getProvisioningError()
  {
    return $this->provisioningError;
  }
  /**
   * Output only. The provisioning state of the deployment group.
   *
   * Accepted values: PROVISIONING_STATE_UNSPECIFIED, PROVISIONING, PROVISIONED,
   * FAILED_TO_PROVISION, DEPROVISIONING, DEPROVISIONED, FAILED_TO_DEPROVISION
   *
   * @param self::PROVISIONING_STATE_* $provisioningState
   */
  public function setProvisioningState($provisioningState)
  {
    $this->provisioningState = $provisioningState;
  }
  /**
   * @return self::PROVISIONING_STATE_*
   */
  public function getProvisioningState()
  {
    return $this->provisioningState;
  }
  /**
   * Output only. Additional information regarding the current provisioning
   * state.
   *
   * @param string $provisioningStateDescription
   */
  public function setProvisioningStateDescription($provisioningStateDescription)
  {
    $this->provisioningStateDescription = $provisioningStateDescription;
  }
  /**
   * @return string
   */
  public function getProvisioningStateDescription()
  {
    return $this->provisioningStateDescription;
  }
  /**
   * Output only. Current state of the deployment group.
   *
   * Accepted values: STATE_UNSPECIFIED, CREATING, ACTIVE, UPDATING, DELETING,
   * FAILED, SUSPENDED, DELETED
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
   * Output only. Time when the deployment group was last updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeploymentGroup::class, 'Google_Service_Config_DeploymentGroup');
