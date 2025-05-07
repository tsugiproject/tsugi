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

namespace Google\Service\CloudWorkstations\Resource;

use Google\Service\CloudWorkstations\GenerateAccessTokenRequest;
use Google\Service\CloudWorkstations\GenerateAccessTokenResponse;
use Google\Service\CloudWorkstations\ListUsableWorkstationsResponse;
use Google\Service\CloudWorkstations\ListWorkstationsResponse;
use Google\Service\CloudWorkstations\Operation;
use Google\Service\CloudWorkstations\Policy;
use Google\Service\CloudWorkstations\SetIamPolicyRequest;
use Google\Service\CloudWorkstations\StartWorkstationRequest;
use Google\Service\CloudWorkstations\StopWorkstationRequest;
use Google\Service\CloudWorkstations\TestIamPermissionsRequest;
use Google\Service\CloudWorkstations\TestIamPermissionsResponse;
use Google\Service\CloudWorkstations\Workstation;

/**
 * The "workstations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $workstationsService = new Google\Service\CloudWorkstations(...);
 *   $workstations = $workstationsService->projects_locations_workstationClusters_workstationConfigs_workstations;
 *  </code>
 */
class ProjectsLocationsWorkstationClustersWorkstationConfigsWorkstations extends \Google\Service\Resource
{
  /**
   * Creates a new workstation. (workstations.create)
   *
   * @param string $parent Required. Parent resource name.
   * @param Workstation $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool validateOnly Optional. If set, validate the request and
   * preview the review, but do not actually apply it.
   * @opt_param string workstationId Required. ID to use for the workstation.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, Workstation $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes the specified workstation. (workstations.delete)
   *
   * @param string $name Required. Name of the workstation to delete.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string etag Optional. If set, the request will be rejected if the
   * latest version of the workstation on the server does not have this ETag.
   * @opt_param bool validateOnly Optional. If set, validate the request and
   * preview the review, but do not actually apply it.
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
   * Returns a short-lived credential that can be used to send authenticated and
   * authorized traffic to a workstation. Once generated this token cannot be
   * revoked and is good for the lifetime of the token.
   * (workstations.generateAccessToken)
   *
   * @param string $workstation Required. Name of the workstation for which the
   * access token should be generated.
   * @param GenerateAccessTokenRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GenerateAccessTokenResponse
   * @throws \Google\Service\Exception
   */
  public function generateAccessToken($workstation, GenerateAccessTokenRequest $postBody, $optParams = [])
  {
    $params = ['workstation' => $workstation, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('generateAccessToken', [$params], GenerateAccessTokenResponse::class);
  }
  /**
   * Returns the requested workstation. (workstations.get)
   *
   * @param string $name Required. Name of the requested resource.
   * @param array $optParams Optional parameters.
   * @return Workstation
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Workstation::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (workstations.getIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * requested. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int options.requestedPolicyVersion Optional. The maximum policy
   * version that will be used to format the policy. Valid values are 0, 1, and 3.
   * Requests specifying an invalid value will be rejected. Requests for policies
   * with any conditional role bindings must specify version 3. Policies with no
   * conditional role bindings may specify any valid value or leave the field
   * unset. The policy in the response might use the policy version that you
   * specified, or it might use a lower policy version. For example, if you
   * specify version 3, but the policy has no conditional role bindings, the
   * response uses version 1. To learn which resources support conditions in their
   * IAM policies, see the [IAM
   * documentation](https://cloud.google.com/iam/help/conditions/resource-
   * policies).
   * @return Policy
   * @throws \Google\Service\Exception
   */
  public function getIamPolicy($resource, $optParams = [])
  {
    $params = ['resource' => $resource];
    $params = array_merge($params, $optParams);
    return $this->call('getIamPolicy', [$params], Policy::class);
  }
  /**
   * Returns all Workstations using the specified workstation configuration. (work
   * stations.listProjectsLocationsWorkstationClustersWorkstationConfigsWorkstatio
   * ns)
   *
   * @param string $parent Required. Parent resource name.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. Maximum number of items to return.
   * @opt_param string pageToken Optional. next_page_token value returned from a
   * previous List request, if any.
   * @return ListWorkstationsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsWorkstationClustersWorkstationConfigsWorkstations($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListWorkstationsResponse::class);
  }
  /**
   * Returns all workstations using the specified workstation configuration on
   * which the caller has the "workstations.workstations.use" permission.
   * (workstations.listUsable)
   *
   * @param string $parent Required. Parent resource name.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. Maximum number of items to return.
   * @opt_param string pageToken Optional. next_page_token value returned from a
   * previous List request, if any.
   * @return ListUsableWorkstationsResponse
   * @throws \Google\Service\Exception
   */
  public function listUsable($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('listUsable', [$params], ListUsableWorkstationsResponse::class);
  }
  /**
   * Updates an existing workstation. (workstations.patch)
   *
   * @param string $name Identifier. Full name of this workstation.
   * @param Workstation $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool allowMissing Optional. If set and the workstation
   * configuration is not found, a new workstation configuration is created. In
   * this situation, update_mask is ignored.
   * @opt_param string updateMask Required. Mask specifying which fields in the
   * workstation configuration should be updated.
   * @opt_param bool validateOnly Optional. If set, validate the request and
   * preview the review, but do not actually apply it.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Workstation $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. Can return `NOT_FOUND`, `INVALID_ARGUMENT`, and
   * `PERMISSION_DENIED` errors. (workstations.setIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * specified. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param SetIamPolicyRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Policy
   * @throws \Google\Service\Exception
   */
  public function setIamPolicy($resource, SetIamPolicyRequest $postBody, $optParams = [])
  {
    $params = ['resource' => $resource, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('setIamPolicy', [$params], Policy::class);
  }
  /**
   * Starts running a workstation so that users can connect to it.
   * (workstations.start)
   *
   * @param string $name Required. Name of the workstation to start.
   * @param StartWorkstationRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function start($name, StartWorkstationRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('start', [$params], Operation::class);
  }
  /**
   * Stops running a workstation, reducing costs. (workstations.stop)
   *
   * @param string $name Required. Name of the workstation to stop.
   * @param StopWorkstationRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function stop($name, StopWorkstationRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('stop', [$params], Operation::class);
  }
  /**
   * Returns permissions that a caller has on the specified resource. If the
   * resource does not exist, this will return an empty set of permissions, not a
   * `NOT_FOUND` error. Note: This operation is designed to be used for building
   * permission-aware UIs and command-line tools, not for authorization checking.
   * This operation may "fail open" without warning.
   * (workstations.testIamPermissions)
   *
   * @param string $resource REQUIRED: The resource for which the policy detail is
   * being requested. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param TestIamPermissionsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return TestIamPermissionsResponse
   * @throws \Google\Service\Exception
   */
  public function testIamPermissions($resource, TestIamPermissionsRequest $postBody, $optParams = [])
  {
    $params = ['resource' => $resource, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('testIamPermissions', [$params], TestIamPermissionsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsWorkstationClustersWorkstationConfigsWorkstations::class, 'Google_Service_CloudWorkstations_Resource_ProjectsLocationsWorkstationClustersWorkstationConfigsWorkstations');
