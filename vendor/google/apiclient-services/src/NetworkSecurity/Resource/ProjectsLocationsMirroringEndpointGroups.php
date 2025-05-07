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

namespace Google\Service\NetworkSecurity\Resource;

use Google\Service\NetworkSecurity\ListMirroringEndpointGroupsResponse;
use Google\Service\NetworkSecurity\MirroringEndpointGroup;
use Google\Service\NetworkSecurity\Operation;

/**
 * The "mirroringEndpointGroups" collection of methods.
 * Typical usage is:
 *  <code>
 *   $networksecurityService = new Google\Service\NetworkSecurity(...);
 *   $mirroringEndpointGroups = $networksecurityService->projects_locations_mirroringEndpointGroups;
 *  </code>
 */
class ProjectsLocationsMirroringEndpointGroups extends \Google\Service\Resource
{
  /**
   * Creates an endpoint group in a given project and location. See
   * https://google.aip.dev/133. (mirroringEndpointGroups.create)
   *
   * @param string $parent Required. Value for parent.
   * @param MirroringEndpointGroup $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string mirroringEndpointGroupId Required. Id of the requesting
   * object If auto-generating Id server-side, remove this field and
   * mirroring_endpoint_group_id from the method_signature of Create RPC
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
  public function create($parent, MirroringEndpointGroup $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes an endpoint group. See https://google.aip.dev/135.
   * (mirroringEndpointGroups.delete)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   *
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
   * Gets a specific endpoint group. See https://google.aip.dev/131.
   * (mirroringEndpointGroups.get)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   * @return MirroringEndpointGroup
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], MirroringEndpointGroup::class);
  }
  /**
   * Lists endpoint groups in a given project and location. See
   * https://google.aip.dev/132.
   * (mirroringEndpointGroups.listProjectsLocationsMirroringEndpointGroups)
   *
   * @param string $parent Required. Parent value for
   * ListMirroringEndpointGroupsRequest
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filtering results
   * @opt_param string orderBy Optional. Hint for how to order the results
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListMirroringEndpointGroupsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsMirroringEndpointGroups($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListMirroringEndpointGroupsResponse::class);
  }
  /**
   * Updates an endpoint group. See https://google.aip.dev/134.
   * (mirroringEndpointGroups.patch)
   *
   * @param string $name Immutable. Identifier. The resource name of this endpoint
   * group, for example:
   * `projects/123456789/locations/global/mirroringEndpointGroups/my-eg`. See
   * https://google.aip.dev/122 for more details.
   * @param MirroringEndpointGroup $postBody
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
   * @opt_param string updateMask Required. Field mask is used to specify the
   * fields to be overwritten in the MirroringEndpointGroup resource by the
   * update. The fields specified in the update_mask are relative to the resource,
   * not the full request. A field will be overwritten if it is in the mask. If
   * the user does not provide a mask then all fields will be overwritten.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, MirroringEndpointGroup $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsMirroringEndpointGroups::class, 'Google_Service_NetworkSecurity_Resource_ProjectsLocationsMirroringEndpointGroups');
