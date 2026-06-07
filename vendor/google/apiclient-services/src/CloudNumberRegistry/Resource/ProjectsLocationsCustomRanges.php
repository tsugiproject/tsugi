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

use Google\Service\CloudNumberRegistry\CustomRange;
use Google\Service\CloudNumberRegistry\FindCustomRangeFreeIpRangesResponse;
use Google\Service\CloudNumberRegistry\ListCustomRangesResponse;
use Google\Service\CloudNumberRegistry\Operation;
use Google\Service\CloudNumberRegistry\ShowCustomRangeUtilizationResponse;

/**
 * The "customRanges" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudnumberregistryService = new Google\Service\CloudNumberRegistry(...);
 *   $customRanges = $cloudnumberregistryService->projects_locations_customRanges;
 *  </code>
 */
class ProjectsLocationsCustomRanges extends \Google\Service\Resource
{
  /**
   * Creates a new CustomRange in a given project and location.
   * (customRanges.create)
   *
   * @param string $parent Required. Value for parent.
   * @param CustomRange $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string customRangeId Required. Id of the requesting object.
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
  public function create($parent, CustomRange $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single CustomRange. (customRanges.delete)
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
   * Finds free IP ranges in a single CustomRange. (customRanges.findFreeIpRanges)
   *
   * @param string $name Required. Name of the CustomRange.
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
   * @return FindCustomRangeFreeIpRangesResponse
   * @throws \Google\Service\Exception
   */
  public function findFreeIpRanges($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('findFreeIpRanges', [$params], FindCustomRangeFreeIpRangesResponse::class);
  }
  /**
   * Gets details of a single CustomRange. (customRanges.get)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   * @return CustomRange
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], CustomRange::class);
  }
  /**
   * Lists CustomRanges in a given project and location.
   * (customRanges.listProjectsLocationsCustomRanges)
   *
   * @param string $parent Required. Parent value for ListCustomRangesRequest
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filtering results.
   * @opt_param string orderBy Optional. Hint for how to order the results.
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListCustomRangesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsCustomRanges($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListCustomRangesResponse::class);
  }
  /**
   * Updates the parameters of a single CustomRange. (customRanges.patch)
   *
   * @param string $name Required. Identifier. name of resource
   * @param CustomRange $postBody
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
   * fields to be overwritten in the CustomRange resource by the update. The
   * fields specified in the update_mask are relative to the resource, not the
   * full request. A field will be overwritten if it is in the mask. If the user
   * does not provide a mask then all fields will be overwritten.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, CustomRange $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Gets the details of a single CustomRange and its utilization.
   * (customRanges.showUtilization)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   * @return ShowCustomRangeUtilizationResponse
   * @throws \Google\Service\Exception
   */
  public function showUtilization($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('showUtilization', [$params], ShowCustomRangeUtilizationResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsCustomRanges::class, 'Google_Service_CloudNumberRegistry_Resource_ProjectsLocationsCustomRanges');
