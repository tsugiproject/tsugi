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

namespace Google\Service\AgentRegistry\Resource;

use Google\Service\AgentRegistry\Binding;
use Google\Service\AgentRegistry\FetchAvailableBindingsResponse;
use Google\Service\AgentRegistry\ListBindingsResponse;
use Google\Service\AgentRegistry\Operation;

/**
 * The "bindings" collection of methods.
 * Typical usage is:
 *  <code>
 *   $agentregistryService = new Google\Service\AgentRegistry(...);
 *   $bindings = $agentregistryService->projects_locations_bindings;
 *  </code>
 */
class ProjectsLocationsBindings extends \Google\Service\Resource
{
  /**
   * Creates a new Binding in a given project and location. (bindings.create)
   *
   * @param string $parent Required. The project and location to create the
   * Binding in. Expected format: `projects/{project}/locations/{location}`.
   * @param Binding $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string bindingId Required. The ID to use for the binding, which
   * will become the final component of the binding's resource name. This value
   * should be 4-63 characters, and must conform to RFC-1034. Specifically, it
   * must match the regular expression `^[a-z]([a-z0-9-]{0,61}[a-z0-9])?$`.
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
  public function create($parent, Binding $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single Binding. (bindings.delete)
   *
   * @param string $name Required. The name of the Binding. Format:
   * `projects/{project}/locations/{location}/bindings/{binding}`.
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
   * Fetches available Bindings. (bindings.fetchAvailable)
   *
   * @param string $parent Required. The parent, in the format
   * `projects/{project}/locations/{location}`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. Page size is 500 if unspecified and is capped at
   * `500` even if a larger value is given.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @opt_param string sourceIdentifier The identifier of the source Agent.
   * Format: * `urn:agent:{publisher}:{namespace}:{name}`
   * @opt_param string targetIdentifier Optional. The identifier of the target
   * Agent, MCP Server, or Endpoint. Format: *
   * `urn:agent:{publisher}:{namespace}:{name}` *
   * `urn:mcp:{publisher}:{namespace}:{name}` *
   * `urn:endpoint:{publisher}:{namespace}:{name}`
   * @return FetchAvailableBindingsResponse
   * @throws \Google\Service\Exception
   */
  public function fetchAvailable($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('fetchAvailable', [$params], FetchAvailableBindingsResponse::class);
  }
  /**
   * Gets details of a single Binding. (bindings.get)
   *
   * @param string $name Required. The name of the Binding. Format:
   * `projects/{project}/locations/{location}/bindings/{binding}`.
   * @param array $optParams Optional parameters.
   * @return Binding
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Binding::class);
  }
  /**
   * Lists Bindings in a given project and location.
   * (bindings.listProjectsLocationsBindings)
   *
   * @param string $parent Required. The project and location to list bindings in.
   * Expected format: `projects/{project}/locations/{location}`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. A query string used to filter the list of
   * bindings returned. The filter expression must follow AIP-160 syntax.
   * @opt_param string orderBy Optional. Hint for how to order the results
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. Page size is 500 if unspecified and is capped at
   * `500` even if a larger value is given.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListBindingsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsBindings($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListBindingsResponse::class);
  }
  /**
   * Updates the parameters of a single Binding. (bindings.patch)
   *
   * @param string $name Required. Identifier. The resource name of the Binding.
   * Format: `projects/{project}/locations/{location}/bindings/{binding}`.
   * @param Binding $postBody
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
   * fields to be overwritten in the Binding resource by the update. The fields
   * specified in the update_mask are relative to the resource, not the full
   * request. A field will be overwritten if it is in the mask. If the user does
   * not provide a mask then all fields present in the request will be
   * overwritten.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Binding $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsBindings::class, 'Google_Service_AgentRegistry_Resource_ProjectsLocationsBindings');
