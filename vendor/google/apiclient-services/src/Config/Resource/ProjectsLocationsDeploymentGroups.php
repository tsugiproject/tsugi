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

namespace Google\Service\Config\Resource;

use Google\Service\Config\DeploymentGroup;
use Google\Service\Config\DeprovisionDeploymentGroupRequest;
use Google\Service\Config\ListDeploymentGroupsResponse;
use Google\Service\Config\Operation;
use Google\Service\Config\ProvisionDeploymentGroupRequest;

/**
 * The "deploymentGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $configService = new Google\Service\Config(...);
 *   $deploymentGroups = $configService->projects_locations_deploymentGroups;
 *  </code>
 */
class ProjectsLocationsDeploymentGroups extends \Google\Service\Resource
{
  /**
   * Creates a DeploymentGroup The newly created DeploymentGroup will be in the
   * `CREATING` state and can be retrieved via Get and List calls.
   * (deploymentGroups.create)
   *
   * @param string $parent Required. The parent in whose context the Deployment
   * Group is created. The parent value is in the format:
   * 'projects/{project_id}/locations/{location}'
   * @param DeploymentGroup $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string deploymentGroupId Required. The deployment group ID.
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes since the first
   * request. For example, consider a situation where you make an initial request
   * and the request times out. If you make the request again with the same
   * request ID, the server can check if original operation with the same request
   * ID was received, and if so, will ignore the second request. This prevents
   * clients from accidentally creating duplicate commitments. The request ID must
   * be a valid UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, DeploymentGroup $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a DeploymentGroup (deploymentGroups.delete)
   *
   * @param string $name Required. The name of DeploymentGroup in the format proje
   * cts/{project_id}/locations/{location_id}/deploymentGroups/{deploymentGroup}
   * @param array $optParams Optional parameters.
   *
   * @opt_param string deploymentReferencePolicy Optional. Policy on how to handle
   * referenced deployments when deleting the DeploymentGroup. If unspecified, the
   * default behavior is to fail the deletion if any deployments currently
   * referenced in the `deployment_units` of the DeploymentGroup or in the latest
   * revision are not deleted.
   * @opt_param bool force Optional. If set to true, any revisions for this
   * deployment group will also be deleted. (Otherwise, the request will only work
   * if the deployment group has no revisions.)
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes after the first
   * request. For example, consider a situation where you make an initial request
   * and the request times out. If you make the request again with the same
   * request ID, the server can check if original operation with the same request
   * ID was received, and if so, will ignore the second request. This prevents
   * clients from accidentally creating duplicate commitments. The request ID must
   * be a valid UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], Operation::class);
  }
  /**
   * Deprovisions a deployment group. NOTE: As a first step of this operation,
   * Infra Manager will automatically delete any Deployments that were part of the
   * *last successful* DeploymentGroupRevision but are *no longer* included in the
   * *current* DeploymentGroup definition (e.g., following an
   * `UpdateDeploymentGroup` call), along with their actuated resources.
   * (deploymentGroups.deprovision)
   *
   * @param string $name Required. The name of the deployment group to
   * deprovision. Format: 'projects/{project_id}/locations/{location}/deploymentGr
   * oups/{deployment_group}'.
   * @param DeprovisionDeploymentGroupRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function deprovision($name, DeprovisionDeploymentGroupRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('deprovision', [$params], Operation::class);
  }
  /**
   * Get a DeploymentGroup for a given project and location.
   * (deploymentGroups.get)
   *
   * @param string $name Required. The name of the deployment group to retrieve.
   * Format: 'projects/{project_id}/locations/{location}/deploymentGroups/{deploym
   * ent_group}'.
   * @param array $optParams Optional parameters.
   * @return DeploymentGroup
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], DeploymentGroup::class);
  }
  /**
   * List DeploymentGroups for a given project and location.
   * (deploymentGroups.listProjectsLocationsDeploymentGroups)
   *
   * @param string $parent Required. The parent, which owns this collection of
   * deployment groups. Format: 'projects/{project_id}/locations/{location}'.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Lists the DeploymentGroups that match the
   * filter expression. A filter expression filters the deployment groups listed
   * in the response. The expression must be of the form '{field} {operator}
   * {value}' where operators: '<', '>', '<=', '>=', '!=', '=', ':' are supported
   * (colon ':' represents a HAS operator which is roughly synonymous with
   * equality). {field} can refer to a proto or JSON field, or a synthetic field.
   * Field names can be camelCase or snake_case. Examples: - Filter by name: name
   * = "projects/foo/locations/us-central1/deploymentGroups/bar" - Filter by
   * labels: - Resources that have a key called 'foo' labels.foo:* - Resources
   * that have a key called 'foo' whose value is 'bar' labels.foo = bar - Filter
   * by state: - DeploymentGroups in CREATING state. state=CREATING
   * @opt_param string orderBy Optional. Field to use to sort the list.
   * @opt_param int pageSize Optional. When requesting a page of resources,
   * 'page_size' specifies number of resources to return. If unspecified, at most
   * 500 will be returned. The maximum value is 1000.
   * @opt_param string pageToken Optional. Token returned by previous call to
   * 'ListDeploymentGroups' which specifies the position in the list from where to
   * continue listing the deployment groups.
   * @return ListDeploymentGroupsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsDeploymentGroups($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListDeploymentGroupsResponse::class);
  }
  /**
   * Updates a DeploymentGroup (deploymentGroups.patch)
   *
   * @param string $name Identifier. The name of the deployment group. Format: 'pr
   * ojects/{project_id}/locations/{location}/deploymentGroups/{deployment_group}'
   * .
   * @param DeploymentGroup $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes since the first
   * request. For example, consider a situation where you make an initial request
   * and the request times out. If you make the request again with the same
   * request ID, the server can check if original operation with the same request
   * ID was received, and if so, will ignore the second request. This prevents
   * clients from accidentally creating duplicate commitments. The request ID must
   * be a valid UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @opt_param string updateMask Optional. Field mask used to specify the fields
   * to be overwritten in the Deployment Group resource by the update. The fields
   * specified in the update_mask are relative to the resource, not the full
   * request. A field will be overwritten if it is in the mask. If the user does
   * not provide a mask then all fields will be overwritten.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, DeploymentGroup $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Provisions a deployment group. NOTE: As a first step of this operation, Infra
   * Manager will automatically delete any Deployments that were part of the *last
   * successful* DeploymentGroupRevision but are *no longer* included in the
   * *current* DeploymentGroup definition (e.g., following an
   * `UpdateDeploymentGroup` call), along with their actuated resources.
   * (deploymentGroups.provision)
   *
   * @param string $name Required. The name of the deployment group to provision.
   * Format: 'projects/{project_id}/locations/{location}/deploymentGroups/{deploym
   * ent_group}'.
   * @param ProvisionDeploymentGroupRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function provision($name, ProvisionDeploymentGroupRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('provision', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDeploymentGroups::class, 'Google_Service_Config_Resource_ProjectsLocationsDeploymentGroups');
