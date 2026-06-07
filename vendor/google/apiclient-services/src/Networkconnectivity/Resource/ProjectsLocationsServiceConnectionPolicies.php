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
use Google\Service\Networkconnectivity\ListServiceConnectionPoliciesResponse;
use Google\Service\Networkconnectivity\ServiceConnectionPolicy;

/**
 * The "serviceConnectionPolicies" collection of methods.
 * Typical usage is:
 *  <code>
 *   $networkconnectivityService = new Google\Service\Networkconnectivity(...);
 *   $serviceConnectionPolicies = $networkconnectivityService->projects_locations_serviceConnectionPolicies;
 *  </code>
 */
class ProjectsLocationsServiceConnectionPolicies extends \Google\Service\Resource
{
  /**
   * Creates a new ServiceConnectionPolicy in a given project and location.
   * (serviceConnectionPolicies.create)
   *
   * @param string $parent Required. The parent resource's name of the
   * ServiceConnectionPolicy. ex. projects/123/locations/us-east1
   * @param ServiceConnectionPolicy $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string autoSubnetworkConfig.allocRangeSpace Optional. The space
   * where we search for a free range to create a subnetwork. It can be narrow
   * down or pick a different space. This is in standard CIDR format. If not
   * specified, “10.0.0.0/8” is used. Only eligible for IPV4_ONLY and IPV4_IPV6
   * subnetwork.
   * @opt_param string autoSubnetworkConfig.ipStack Optional. The requested IP
   * stack for the subnetwork. If not specified, IPv4 is used.
   * @opt_param int autoSubnetworkConfig.prefixLength Optional. The desired prefix
   * length for the subnet's IP address range. E.g., 24 for a /24. The actual
   * range is allocated from available space. If not specified, 24 is used. Only
   * eligible for IPV4_ONLY and IPV4_IPV6 subnetwork.
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
   * @opt_param string serviceConnectionPolicyId Optional. Resource ID (i.e. 'foo'
   * in '[...]/projects/p/locations/l/serviceConnectionPolicies/foo') See
   * https://google.aip.dev/122#resource-id-segments Unique per location.
   * @opt_param string subnetworkMode Optional. If this field is not set,
   * USER_PROVIDED is the inferred value to use.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function create($parent, ServiceConnectionPolicy $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Deletes a single ServiceConnectionPolicy. (serviceConnectionPolicies.delete)
   *
   * @param string $name Required. The name of the ServiceConnectionPolicy to
   * delete.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string etag Optional. The etag is computed by the server, and may
   * be sent on update and delete requests to ensure the client has an up-to-date
   * value before proceeding.
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
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Gets details of a single ServiceConnectionPolicy.
   * (serviceConnectionPolicies.get)
   *
   * @param string $name Required. Name of the ServiceConnectionPolicy to get.
   * @param array $optParams Optional parameters.
   * @return ServiceConnectionPolicy
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], ServiceConnectionPolicy::class);
  }
  /**
   * Lists ServiceConnectionPolicies in a given project and location.
   * (serviceConnectionPolicies.listProjectsLocationsServiceConnectionPolicies)
   *
   * @param string $parent Required. The parent resource's name. ex.
   * projects/123/locations/us-east1
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters the results listed
   * in the response.
   * @opt_param string orderBy Sort the results by a certain order.
   * @opt_param int pageSize The maximum number of results per page that should be
   * returned.
   * @opt_param string pageToken The page token.
   * @return ListServiceConnectionPoliciesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsServiceConnectionPolicies($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListServiceConnectionPoliciesResponse::class);
  }
  /**
   * Updates the parameters of a single ServiceConnectionPolicy.
   * (serviceConnectionPolicies.patch)
   *
   * @param string $name Immutable. The name of a ServiceConnectionPolicy. Format:
   * projects/{project}/locations/{location}/serviceConnectionPolicies/{service_co
   * nnection_policy} See: https://google.aip.dev/122#fields-representing-
   * resource-names
   * @param ServiceConnectionPolicy $postBody
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
   * fields to be overwritten in the ServiceConnectionPolicy resource by the
   * update. The fields specified in the update_mask are relative to the resource,
   * not the full request. A field will be overwritten if it is in the mask. If
   * the user does not provide a mask then all fields will be overwritten.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function patch($name, ServiceConnectionPolicy $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], GoogleLongrunningOperation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsServiceConnectionPolicies::class, 'Google_Service_Networkconnectivity_Resource_ProjectsLocationsServiceConnectionPolicies');
