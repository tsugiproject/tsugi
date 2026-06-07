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

class ProvisionDeploymentGroupRequest extends \Google\Model
{
  protected $deploymentSpecsType = DeploymentSpec::class;
  protected $deploymentSpecsDataType = 'map';

  /**
   * Optional. The deployment specs of the deployment units to be created within
   * the same project and location of the deployment group. The key is the unit
   * ID, and the value is the `DeploymentSpec`. Provisioning will fail if a
   * `deployment_spec` has a `deployment_id` that matches an existing deployment
   * in the same project and location. If an existing deployment was part of the
   * last successful revision but is no longer in the current DeploymentGroup's
   * `deployment_units`, it will be recreated if included in `deployment_specs`.
   *
   * @param DeploymentSpec[] $deploymentSpecs
   */
  public function setDeploymentSpecs($deploymentSpecs)
  {
    $this->deploymentSpecs = $deploymentSpecs;
  }
  /**
   * @return DeploymentSpec[]
   */
  public function getDeploymentSpecs()
  {
    return $this->deploymentSpecs;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProvisionDeploymentGroupRequest::class, 'Google_Service_Config_ProvisionDeploymentGroupRequest');
