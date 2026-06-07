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

use Google\Service\CloudNumberRegistry\DiscoveredRange;
use Google\Service\CloudNumberRegistry\FindDiscoveredRangeFreeIpRangesResponse;
use Google\Service\CloudNumberRegistry\ListDiscoveredRangesResponse;
use Google\Service\CloudNumberRegistry\ShowDiscoveredRangeUtilizationResponse;

/**
 * The "discoveredRanges" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudnumberregistryService = new Google\Service\CloudNumberRegistry(...);
 *   $discoveredRanges = $cloudnumberregistryService->projects_locations_discoveredRanges;
 *  </code>
 */
class ProjectsLocationsDiscoveredRanges extends \Google\Service\Resource
{
  /**
   * Finds free IP ranges in a single DiscoveredRange.
   * (discoveredRanges.findFreeIpRanges)
   *
   * @param string $name Required. Name of the DiscoveredRange.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int cidrPrefixLength Required. The prefix length of the free IP
   * ranges to find.
   * @opt_param int rangeCount Optional. The number of free IP ranges to find.
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
   * @return FindDiscoveredRangeFreeIpRangesResponse
   * @throws \Google\Service\Exception
   */
  public function findFreeIpRanges($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('findFreeIpRanges', [$params], FindDiscoveredRangeFreeIpRangesResponse::class);
  }
  /**
   * Gets details of a single DiscoveredRange. (discoveredRanges.get)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   * @return DiscoveredRange
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], DiscoveredRange::class);
  }
  /**
   * Lists DiscoveredRanges in a given project and location.
   * (discoveredRanges.listProjectsLocationsDiscoveredRanges)
   *
   * @param string $parent Required. Parent value for ListDiscoveredRangesRequest
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filtering results.
   * @opt_param string orderBy Optional. Hint for how to order the results.
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListDiscoveredRangesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsDiscoveredRanges($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListDiscoveredRangesResponse::class);
  }
  /**
   * Gets the details of a single DiscoveredRange and its utilization.
   * (discoveredRanges.showUtilization)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   * @return ShowDiscoveredRangeUtilizationResponse
   * @throws \Google\Service\Exception
   */
  public function showUtilization($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('showUtilization', [$params], ShowDiscoveredRangeUtilizationResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDiscoveredRanges::class, 'Google_Service_CloudNumberRegistry_Resource_ProjectsLocationsDiscoveredRanges');
