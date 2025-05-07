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

namespace Google\Service\AnalyticsHub\Resource;

use Google\Service\AnalyticsHub\AnalyticshubEmpty;
use Google\Service\AnalyticsHub\DataExchange;
use Google\Service\AnalyticsHub\GetIamPolicyRequest;
use Google\Service\AnalyticsHub\ListDataExchangesResponse;
use Google\Service\AnalyticsHub\ListSharedResourceSubscriptionsResponse;
use Google\Service\AnalyticsHub\Operation;
use Google\Service\AnalyticsHub\Policy;
use Google\Service\AnalyticsHub\SetIamPolicyRequest;
use Google\Service\AnalyticsHub\SubscribeDataExchangeRequest;
use Google\Service\AnalyticsHub\TestIamPermissionsRequest;
use Google\Service\AnalyticsHub\TestIamPermissionsResponse;

/**
 * The "dataExchanges" collection of methods.
 * Typical usage is:
 *  <code>
 *   $analyticshubService = new Google\Service\AnalyticsHub(...);
 *   $dataExchanges = $analyticshubService->projects_locations_dataExchanges;
 *  </code>
 */
class ProjectsLocationsDataExchanges extends \Google\Service\Resource
{
  /**
   * Creates a new data exchange. (dataExchanges.create)
   *
   * @param string $parent Required. The parent resource path of the data
   * exchange. e.g. `projects/myproject/locations/US`.
   * @param DataExchange $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string dataExchangeId Required. The ID of the data exchange. Must
   * contain only Unicode letters, numbers (0-9), underscores (_). Should not use
   * characters that require URL-escaping, or characters outside of ASCII, spaces.
   * Max length: 100 bytes.
   * @return DataExchange
   * @throws \Google\Service\Exception
   */
  public function create($parent, DataExchange $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], DataExchange::class);
  }
  /**
   * Deletes an existing data exchange. (dataExchanges.delete)
   *
   * @param string $name Required. The full name of the data exchange resource
   * that you want to delete. For example,
   * `projects/myproject/locations/US/dataExchanges/123`.
   * @param array $optParams Optional parameters.
   * @return AnalyticshubEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], AnalyticshubEmpty::class);
  }
  /**
   * Gets the details of a data exchange. (dataExchanges.get)
   *
   * @param string $name Required. The resource name of the data exchange. e.g.
   * `projects/myproject/locations/US/dataExchanges/123`.
   * @param array $optParams Optional parameters.
   * @return DataExchange
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], DataExchange::class);
  }
  /**
   * Gets the IAM policy. (dataExchanges.getIamPolicy)
   *
   * @param string $resource REQUIRED: The resource for which the policy is being
   * requested. See [Resource
   * names](https://cloud.google.com/apis/design/resource_names) for the
   * appropriate value for this field.
   * @param GetIamPolicyRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Policy
   * @throws \Google\Service\Exception
   */
  public function getIamPolicy($resource, GetIamPolicyRequest $postBody, $optParams = [])
  {
    $params = ['resource' => $resource, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('getIamPolicy', [$params], Policy::class);
  }
  /**
   * Lists all data exchanges in a given project and location.
   * (dataExchanges.listProjectsLocationsDataExchanges)
   *
   * @param string $parent Required. The parent resource path of the data
   * exchanges. e.g. `projects/myproject/locations/US`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize The maximum number of results to return in a single
   * response page. Leverage the page tokens to iterate through the entire
   * collection.
   * @opt_param string pageToken Page token, returned by a previous call, to
   * request the next page of results.
   * @return ListDataExchangesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsDataExchanges($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListDataExchangesResponse::class);
  }
  /**
   * Lists all subscriptions on a given Data Exchange or Listing.
   * (dataExchanges.listSubscriptions)
   *
   * @param string $resource Required. Resource name of the requested target. This
   * resource may be either a Listing or a DataExchange. e.g.
   * projects/123/locations/US/dataExchanges/456 OR e.g.
   * projects/123/locations/US/dataExchanges/456/listings/789
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool includeDeletedSubscriptions If selected, includes deleted
   * subscriptions in the response (up to 63 days after deletion).
   * @opt_param int pageSize The maximum number of results to return in a single
   * response page.
   * @opt_param string pageToken Page token, returned by a previous call.
   * @return ListSharedResourceSubscriptionsResponse
   * @throws \Google\Service\Exception
   */
  public function listSubscriptions($resource, $optParams = [])
  {
    $params = ['resource' => $resource];
    $params = array_merge($params, $optParams);
    return $this->call('listSubscriptions', [$params], ListSharedResourceSubscriptionsResponse::class);
  }
  /**
   * Updates an existing data exchange. (dataExchanges.patch)
   *
   * @param string $name Output only. The resource name of the data exchange. e.g.
   * `projects/myproject/locations/US/dataExchanges/123`.
   * @param DataExchange $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. Field mask specifies the fields to
   * update in the data exchange resource. The fields specified in the
   * `updateMask` are relative to the resource and are not a full request.
   * @return DataExchange
   * @throws \Google\Service\Exception
   */
  public function patch($name, DataExchange $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], DataExchange::class);
  }
  /**
   * Sets the IAM policy. (dataExchanges.setIamPolicy)
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
   * Creates a Subscription to a Data Clean Room. This is a long-running operation
   * as it will create one or more linked datasets. (dataExchanges.subscribe)
   *
   * @param string $name Required. Resource name of the Data Exchange. e.g.
   * `projects/publisherproject/locations/US/dataExchanges/123`
   * @param SubscribeDataExchangeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function subscribe($name, SubscribeDataExchangeRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('subscribe', [$params], Operation::class);
  }
  /**
   * Returns the permissions that a caller has. (dataExchanges.testIamPermissions)
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
class_alias(ProjectsLocationsDataExchanges::class, 'Google_Service_AnalyticsHub_Resource_ProjectsLocationsDataExchanges');
