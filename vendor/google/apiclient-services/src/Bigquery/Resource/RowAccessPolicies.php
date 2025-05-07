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

namespace Google\Service\Bigquery\Resource;

use Google\Service\Bigquery\BatchDeleteRowAccessPoliciesRequest;
use Google\Service\Bigquery\GetIamPolicyRequest;
use Google\Service\Bigquery\ListRowAccessPoliciesResponse;
use Google\Service\Bigquery\Policy;
use Google\Service\Bigquery\RowAccessPolicy;
use Google\Service\Bigquery\TestIamPermissionsRequest;
use Google\Service\Bigquery\TestIamPermissionsResponse;

/**
 * The "rowAccessPolicies" collection of methods.
 * Typical usage is:
 *  <code>
 *   $bigqueryService = new Google\Service\Bigquery(...);
 *   $rowAccessPolicies = $bigqueryService->rowAccessPolicies;
 *  </code>
 */
class RowAccessPolicies extends \Google\Service\Resource
{
  /**
   * Deletes provided row access policies. (rowAccessPolicies.batchDelete)
   *
   * @param string $projectId Required. Project ID of the table to delete the row
   * access policies.
   * @param string $datasetId Required. Dataset ID of the table to delete the row
   * access policies.
   * @param string $tableId Required. Table ID of the table to delete the row
   * access policies.
   * @param BatchDeleteRowAccessPoliciesRequest $postBody
   * @param array $optParams Optional parameters.
   * @throws \Google\Service\Exception
   */
  public function batchDelete($projectId, $datasetId, $tableId, BatchDeleteRowAccessPoliciesRequest $postBody, $optParams = [])
  {
    $params = ['projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('batchDelete', [$params]);
  }
  /**
   * Deletes a row access policy. (rowAccessPolicies.delete)
   *
   * @param string $projectId Required. Project ID of the table to delete the row
   * access policy.
   * @param string $datasetId Required. Dataset ID of the table to delete the row
   * access policy.
   * @param string $tableId Required. Table ID of the table to delete the row
   * access policy.
   * @param string $policyId Required. Policy ID of the row access policy.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force If set to true, it deletes the row access policy even
   * if it's the last row access policy on the table and the deletion will widen
   * the access rather narrowing it.
   * @throws \Google\Service\Exception
   */
  public function delete($projectId, $datasetId, $tableId, $policyId, $optParams = [])
  {
    $params = ['projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'policyId' => $policyId];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params]);
  }
  /**
   * Gets the specified row access policy by policy ID. (rowAccessPolicies.get)
   *
   * @param string $projectId Required. Project ID of the table to get the row
   * access policy.
   * @param string $datasetId Required. Dataset ID of the table to get the row
   * access policy.
   * @param string $tableId Required. Table ID of the table to get the row access
   * policy.
   * @param string $policyId Required. Policy ID of the row access policy.
   * @param array $optParams Optional parameters.
   * @return RowAccessPolicy
   * @throws \Google\Service\Exception
   */
  public function get($projectId, $datasetId, $tableId, $policyId, $optParams = [])
  {
    $params = ['projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'policyId' => $policyId];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], RowAccessPolicy::class);
  }
  /**
   * Gets the access control policy for a resource. Returns an empty policy if the
   * resource exists and does not have a policy set.
   * (rowAccessPolicies.getIamPolicy)
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
   * Creates a row access policy. (rowAccessPolicies.insert)
   *
   * @param string $projectId Required. Project ID of the table to get the row
   * access policy.
   * @param string $datasetId Required. Dataset ID of the table to get the row
   * access policy.
   * @param string $tableId Required. Table ID of the table to get the row access
   * policy.
   * @param RowAccessPolicy $postBody
   * @param array $optParams Optional parameters.
   * @return RowAccessPolicy
   * @throws \Google\Service\Exception
   */
  public function insert($projectId, $datasetId, $tableId, RowAccessPolicy $postBody, $optParams = [])
  {
    $params = ['projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('insert', [$params], RowAccessPolicy::class);
  }
  /**
   * Lists all row access policies on the specified table.
   * (rowAccessPolicies.listRowAccessPolicies)
   *
   * @param string $projectId Required. Project ID of the row access policies to
   * list.
   * @param string $datasetId Required. Dataset ID of row access policies to list.
   * @param string $tableId Required. Table ID of the table to list row access
   * policies.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize The maximum number of results to return in a single
   * response page. Leverage the page tokens to iterate through the entire
   * collection.
   * @opt_param string pageToken Page token, returned by a previous call, to
   * request the next page of results.
   * @return ListRowAccessPoliciesResponse
   * @throws \Google\Service\Exception
   */
  public function listRowAccessPolicies($projectId, $datasetId, $tableId, $optParams = [])
  {
    $params = ['projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListRowAccessPoliciesResponse::class);
  }
  /**
   * Returns permissions that a caller has on the specified resource. If the
   * resource does not exist, this will return an empty set of permissions, not a
   * `NOT_FOUND` error. Note: This operation is designed to be used for building
   * permission-aware UIs and command-line tools, not for authorization checking.
   * This operation may "fail open" without warning.
   * (rowAccessPolicies.testIamPermissions)
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
  /**
   * Updates a row access policy. (rowAccessPolicies.update)
   *
   * @param string $projectId Required. Project ID of the table to get the row
   * access policy.
   * @param string $datasetId Required. Dataset ID of the table to get the row
   * access policy.
   * @param string $tableId Required. Table ID of the table to get the row access
   * policy.
   * @param string $policyId Required. Policy ID of the row access policy.
   * @param RowAccessPolicy $postBody
   * @param array $optParams Optional parameters.
   * @return RowAccessPolicy
   * @throws \Google\Service\Exception
   */
  public function update($projectId, $datasetId, $tableId, $policyId, RowAccessPolicy $postBody, $optParams = [])
  {
    $params = ['projectId' => $projectId, 'datasetId' => $datasetId, 'tableId' => $tableId, 'policyId' => $policyId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('update', [$params], RowAccessPolicy::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RowAccessPolicies::class, 'Google_Service_Bigquery_Resource_RowAccessPolicies');
