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

namespace Google\Service\CloudNumberRegistry\Resource;

use Google\Service\CloudNumberRegistry\CheckAvailabilityIpamAdminScopesResponse;
use Google\Service\CloudNumberRegistry\CleanupIpamAdminScopeRequest;
use Google\Service\CloudNumberRegistry\DisableIpamAdminScopeRequest;
use Google\Service\CloudNumberRegistry\IpamAdminScope;
use Google\Service\CloudNumberRegistry\ListIpamAdminScopesResponse;
use Google\Service\CloudNumberRegistry\Operation;

/**
 * The "ipamAdminScopes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudnumberregistryService = new Google\Service\CloudNumberRegistry(...);
 *   $ipamAdminScopes = $cloudnumberregistryService->projects_locations_ipamAdminScopes;
 *  </code>
 */
class ProjectsLocationsIpamAdminScopes extends \Google\Service\Resource
{
  /**
   * Checks the availability of IPAM admin scopes in a given project and location.
   * (ipamAdminScopes.checkAvailability)
   *
   * @param string $parent Required. Parent value for the IpamAdminScopes.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string scopes Required. The scopes of the IpamAdminScopes to look
   * for.
   * @return CheckAvailabilityIpamAdminScopesResponse
   * @throws \Google\Service\Exception
   */
  public function checkAvailability($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('checkAvailability', [$params], CheckAvailabilityIpamAdminScopesResponse::class);
  }
  /**
   * Cleans up a single IpamAdminScope. (ipamAdminScopes.cleanup)
   *
   * @param string $name Required. Name of the resource
   * @param CleanupIpamAdminScopeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function cleanup($name, CleanupIpamAdminScopeRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('cleanup', [$params], Operation::class);
  }
  /**
   * Creates a new IpamAdminScope in a given project and location.
   * (ipamAdminScopes.create)
   *
   * @param string $parent Required. Value for parent.
   * @param IpamAdminScope $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string ipamAdminScopeId Required. Id of the requesting object.
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
  public function create($parent, IpamAdminScope $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single IpamAdminScope. (ipamAdminScopes.delete)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force Optional. If set to true, all associated resources will
   * be deleted.
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
   * Disables a single IpamAdminScope. (ipamAdminScopes.disable)
   *
   * @param string $name Required. Name of the resource
   * @param DisableIpamAdminScopeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function disable($name, DisableIpamAdminScopeRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('disable', [$params], Operation::class);
  }
  /**
   * Gets details of a single IpamAdminScope. (ipamAdminScopes.get)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   * @return IpamAdminScope
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], IpamAdminScope::class);
  }
  /**
   * List all IPAM admin scopes in a given project and location.
   * (ipamAdminScopes.listProjectsLocationsIpamAdminScopes)
   *
   * @param string $parent Required. Parent value for ListIpamAdminScopesRequest
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filtering results
   * @opt_param string orderBy Optional. Hint for how to order the results
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListIpamAdminScopesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsIpamAdminScopes($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListIpamAdminScopesResponse::class);
  }
  /**
   * Updates the parameters of a single IpamAdminScope. (ipamAdminScopes.patch)
   *
   * @param string $name Required. Identifier. name of resource
   * @param IpamAdminScope $postBody
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
   * @opt_param string updateMask Optional. Field mask is used to specify the
   * fields to be overwritten in the IpamAdminScope resource by the update. The
   * fields specified in the update_mask are relative to the resource, not the
   * full request. A field will be overwritten if it is in the mask. If the user
   * does not provide a mask then all fields will be overwritten.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, IpamAdminScope $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsIpamAdminScopes::class, 'Google_Service_CloudNumberRegistry_Resource_ProjectsLocationsIpamAdminScopes');
