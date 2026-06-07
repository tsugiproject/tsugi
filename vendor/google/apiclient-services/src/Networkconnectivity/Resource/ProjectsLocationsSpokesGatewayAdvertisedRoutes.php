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

use Google\Service\Networkconnectivity\GatewayAdvertisedRoute;
use Google\Service\Networkconnectivity\GoogleLongrunningOperation;
use Google\Service\Networkconnectivity\ListGatewayAdvertisedRoutesResponse;

/**
 * The "gatewayAdvertisedRoutes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $networkconnectivityService = new Google\Service\Networkconnectivity(...);
 *   $gatewayAdvertisedRoutes = $networkconnectivityService->projects_locations_spokes_gatewayAdvertisedRoutes;
 *  </code>
 */
class ProjectsLocationsSpokesGatewayAdvertisedRoutes extends \Google\Service\Resource
{
  /**
   * Create a GatewayAdvertisedRoute (gatewayAdvertisedRoutes.create)
   *
   * @param string $parent Required. The parent resource.
   * @param GatewayAdvertisedRoute $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string gatewayAdvertisedRouteId Required. Unique id for the route
   * to create.
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
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function create($parent, GatewayAdvertisedRoute $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Delete a GatewayAdvertisedRoute (gatewayAdvertisedRoutes.delete)
   *
   * @param string $name Required. The name of the gateway advertised route to
   * delete.
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
   * Get a GatewayAdvertisedRoute (gatewayAdvertisedRoutes.get)
   *
   * @param string $name Required. The name of the gateway advertised route to
   * get.
   * @param array $optParams Optional parameters.
   * @return GatewayAdvertisedRoute
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], GatewayAdvertisedRoute::class);
  }
  /**
   * List GatewayAdvertisedRoutes
   * (gatewayAdvertisedRoutes.listProjectsLocationsSpokesGatewayAdvertisedRoutes)
   *
   * @param string $parent Required. The parent resource's name.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter An expression that filters the list of results.
   * @opt_param string orderBy Sort the results by a certain order.
   * @opt_param int pageSize Optional. The maximum number of results per page that
   * should be returned.
   * @opt_param string pageToken Optional. A page token, received from a previous
   * `ListGatewayAdvertisedRoutes` call. Provide this to retrieve the subsequent
   * page. When paginating, all other parameters provided to
   * `ListGatewayAdvertisedRoutes` must match the call that provided the page
   * token.
   * @return ListGatewayAdvertisedRoutesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsSpokesGatewayAdvertisedRoutes($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListGatewayAdvertisedRoutesResponse::class);
  }
  /**
   * Update a GatewayAdvertisedRoute (gatewayAdvertisedRoutes.patch)
   *
   * @param string $name Identifier. The name of the gateway advertised route.
   * Route names must be unique and use the following form: `projects/{project_num
   * ber}/locations/{region}/spokes/{spoke}/gatewayAdvertisedRoutes/{gateway_adver
   * tised_route_id}`
   * @param GatewayAdvertisedRoute $postBody
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
  public function patch($name, GatewayAdvertisedRoute $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], GoogleLongrunningOperation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsSpokesGatewayAdvertisedRoutes::class, 'Google_Service_Networkconnectivity_Resource_ProjectsLocationsSpokesGatewayAdvertisedRoutes');
