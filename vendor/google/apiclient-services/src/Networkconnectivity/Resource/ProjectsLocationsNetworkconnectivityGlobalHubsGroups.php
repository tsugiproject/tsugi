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

namespace Google\Service\Networkconnectivity\Resource;

use Google\Service\Networkconnectivity\GoogleLongrunningOperation;
use Google\Service\Networkconnectivity\Group;
use Google\Service\Networkconnectivity\ListGroupsResponse;
use Google\Service\Networkconnectivity\Policy;
use Google\Service\Networkconnectivity\SetIamPolicyRequest;
use Google\Service\Networkconnectivity\TestIamPermissionsRequest;
use Google\Service\Networkconnectivity\TestIamPermissionsResponse;

/**
 * The "groups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $networkconnectivityService = new Google\Service\Networkconnectivity(...);
 *   $groups = $networkconnectivityService->projects_locations_global_hubs_groups;
 *  </code>
 */
class ProjectsLocationsNetworkconnectivityGlobalHubsGroups extends \Google\Service\Resource
{
  /**
   * Gets details about a Network Connectivity Center group. (groups.get)
   *
   * @param string $name Required. The name of the route table resource.
   * @param array $optParams Optional parameters.
   * @return Group
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Group::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set. (groups.getIamPolicy)
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
   * Lists groups in a given hub.
   * (groups.listProjectsLocationsNetworkconnectivityGlobalHubsGroups)
   *
   * @param string $parent Required. The parent resource's name.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter An expression that filters the list of results.
   * @opt_param string orderBy Sort the results by a certain order.
   * @opt_param int pageSize The maximum number of results to return per page.
   * @opt_param string pageToken The page token.
   * @return ListGroupsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsNetworkconnectivityGlobalHubsGroups($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListGroupsResponse::class);
  }
  /**
   * Updates the parameters of a Network Connectivity Center group. (groups.patch)
   *
   * @param string $name Immutable. The name of the group. Group names must be
   * unique. They use the following form:
   * `projects/{project_number}/locations/global/hubs/{hub}/groups/{group_id}`
   * @param Group $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. A request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server knows to ignore the request if it has already been completed. The
   * server guarantees that a request doesn't result in creation of duplicate
   * commitments for at least 60 minutes. For example, consider a situation where
   * you make an initial request and the request times out. If you make the
   * request again with the same request ID, the server can check to see whether
   * the original operation was received. If it was, the server ignores the second
   * request. This behavior prevents clients from mistakenly creating duplicate
   * commitments. The request ID must be a valid UUID, with the exception that
   * zero UUID is not supported (00000000-0000-0000-0000-000000000000).
   * @opt_param string updateMask Optional. In the case of an update to an
   * existing group, field mask is used to specify the fields to be overwritten.
   * The fields specified in the update_mask are relative to the resource, not the
   * full request. A field is overwritten if it is in the mask. If the user does
   * not provide a mask, then all fields are overwritten.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Group $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Sets the access control policy on the specified resource. Replaces any
   * existing policy. Can return `NOT_FOUND`, `INVALID_ARGUMENT`, and
   * `PERMISSION_DENIED` errors. (groups.setIamPolicy)
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
   * Returns permissions that a caller has on the specified resource. If the
   * resource does not exist, this will return an empty set of permissions, not a
   * `NOT_FOUND` error. Note: This operation is designed to be used for building
   * permission-aware UIs and command-line tools, not for authorization checking.
   * This operation may "fail open" without warning. (groups.testIamPermissions)
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
class_alias(ProjectsLocationsNetworkconnectivityGlobalHubsGroups::class, 'Google_Service_Networkconnectivity_Resource_ProjectsLocationsNetworkconnectivityGlobalHubsGroups');
