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

namespace Google\Service\Compute\Resource;

use Google\Service\Compute\InstanceGroupManager;
use Google\Service\Compute\Operation;
use Google\Service\Compute\RegionInstanceGroupManagerDeleteInstanceConfigReq;
use Google\Service\Compute\RegionInstanceGroupManagerList;
use Google\Service\Compute\RegionInstanceGroupManagerPatchInstanceConfigReq;
use Google\Service\Compute\RegionInstanceGroupManagerUpdateInstanceConfigReq;
use Google\Service\Compute\RegionInstanceGroupManagersAbandonInstancesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersApplyUpdatesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersCreateInstancesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersDeleteInstancesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersListErrorsResponse;
use Google\Service\Compute\RegionInstanceGroupManagersListInstanceConfigsResp;
use Google\Service\Compute\RegionInstanceGroupManagersListInstancesResponse;
use Google\Service\Compute\RegionInstanceGroupManagersRecreateRequest;
use Google\Service\Compute\RegionInstanceGroupManagersResumeInstancesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersSetTargetPoolsRequest;
use Google\Service\Compute\RegionInstanceGroupManagersSetTemplateRequest;
use Google\Service\Compute\RegionInstanceGroupManagersStartInstancesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersStopInstancesRequest;
use Google\Service\Compute\RegionInstanceGroupManagersSuspendInstancesRequest;

/**
 * The "regionInstanceGroupManagers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeService = new Google\Service\Compute(...);
 *   $regionInstanceGroupManagers = $computeService->regionInstanceGroupManagers;
 *  </code>
 */
class RegionInstanceGroupManagers extends \Google\Service\Resource
{
  /**
   * Flags the specified instances to be immediately removed from the managed
   * instance group. Abandoning an instance does not delete the instance, but it
   * does remove the instance from any target pools that are applied by the
   * managed instance group. This method reduces the targetSize of the managed
   * instance group by the number of instances that you abandon. This operation is
   * marked as DONE when the action is scheduled even if the instances have not
   * yet been removed from the group. You must separately verify the status of the
   * abandoning action with the listmanagedinstances method. If the group is part
   * of a backend service that has enabled connection draining, it can take up to
   * 60 seconds after the connection draining duration has elapsed before the VM
   * instance is removed or deleted. You can specify a maximum of 1000 instances
   * with this method per request. (regionInstanceGroupManagers.abandonInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersAbandonInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function abandonInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersAbandonInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('abandonInstances', [$params], Operation::class);
  }
  /**
   * Apply updates to selected instances the managed instance group.
   * (regionInstanceGroupManagers.applyUpdatesToInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request, should conform
   * to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group,
   * should conform to RFC1035.
   * @param RegionInstanceGroupManagersApplyUpdatesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function applyUpdatesToInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersApplyUpdatesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('applyUpdatesToInstances', [$params], Operation::class);
  }
  /**
   * Creates instances with per-instance configurations in this regional managed
   * instance group. Instances are created using the current instance template.
   * The create instances operation is marked DONE if the createInstances request
   * is successful. The underlying actions take additional time. You must
   * separately verify the status of the creating or actions with the
   * listmanagedinstances method. (regionInstanceGroupManagers.createInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region The name of the region where the managed instance group
   * is located. It should conform to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group.
   * It should conform to RFC1035.
   * @param RegionInstanceGroupManagersCreateInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function createInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersCreateInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('createInstances', [$params], Operation::class);
  }
  /**
   * Deletes the specified managed instance group and all of the instances in that
   * group. (regionInstanceGroupManagers.delete)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group to
   * delete.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function delete($project, $region, $instanceGroupManager, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], Operation::class);
  }
  /**
   * Flags the specified instances in the managed instance group to be immediately
   * deleted. The instances are also removed from any target pools of which they
   * were a member. This method reduces the targetSize of the managed instance
   * group by the number of instances that you delete. The deleteInstances
   * operation is marked DONE if the deleteInstances request is successful. The
   * underlying actions take additional time. You must separately verify the
   * status of the deleting action with the listmanagedinstances method. If the
   * group is part of a backend service that has enabled connection draining, it
   * can take up to 60 seconds after the connection draining duration has elapsed
   * before the VM instance is removed or deleted. You can specify a maximum of
   * 1000 instances with this method per request.
   * (regionInstanceGroupManagers.deleteInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersDeleteInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function deleteInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersDeleteInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('deleteInstances', [$params], Operation::class);
  }
  /**
   * Deletes selected per-instance configurations for the managed instance group.
   * (regionInstanceGroupManagers.deletePerInstanceConfigs)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request, should conform
   * to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group.
   * It should conform to RFC1035.
   * @param RegionInstanceGroupManagerDeleteInstanceConfigReq $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function deletePerInstanceConfigs($project, $region, $instanceGroupManager, RegionInstanceGroupManagerDeleteInstanceConfigReq $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('deletePerInstanceConfigs', [$params], Operation::class);
  }
  /**
   * Returns all of the details about the specified managed instance group.
   * (regionInstanceGroupManagers.get)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group to
   * return.
   * @param array $optParams Optional parameters.
   * @return InstanceGroupManager
   * @throws \Google\Service\Exception
   */
  public function get($project, $region, $instanceGroupManager, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], InstanceGroupManager::class);
  }
  /**
   * Creates a managed instance group using the information that you specify in
   * the request. After the group is created, instances in the group are created
   * using the specified instance template. This operation is marked as DONE when
   * the group is created even if the instances in the group have not yet been
   * created. You must separately verify the status of the individual instances
   * with the listmanagedinstances method. A regional managed instance group can
   * contain up to 2000 instances. (regionInstanceGroupManagers.insert)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param InstanceGroupManager $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function insert($project, $region, InstanceGroupManager $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('insert', [$params], Operation::class);
  }
  /**
   * Retrieves the list of managed instance groups that are contained within the
   * specified region.
   * (regionInstanceGroupManagers.listRegionInstanceGroupManagers)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters resources listed in
   * the response. Most Compute resources support two types of filter expressions:
   * expressions that support regular expressions and expressions that follow API
   * improvement proposal AIP-160. These two types of filter expressions cannot be
   * mixed in one request. If you want to use AIP-160, your expression must
   * specify the field name, an operator, and the value that you want to use for
   * filtering. The value must be a string, a number, or a boolean. The operator
   * must be either `=`, `!=`, `>`, `<`, `<=`, `>=` or `:`. For example, if you
   * are filtering Compute Engine instances, you can exclude instances named
   * `example-instance` by specifying `name != example-instance`. The `:*`
   * comparison can be used to test whether a key has been defined. For example,
   * to find all objects with `owner` label use: ``` labels.owner:* ``` You can
   * also filter nested fields. For example, you could specify
   * `scheduling.automaticRestart = false` to include instances only if they are
   * not scheduled for automatic restarts. You can use filtering on nested fields
   * to filter based on resource labels. To filter on multiple expressions,
   * provide each separate expression within parentheses. For example: ```
   * (scheduling.automaticRestart = true) (cpuPlatform = "Intel Skylake") ``` By
   * default, each expression is an `AND` expression. However, you can include
   * `AND` and `OR` expressions explicitly. For example: ``` (cpuPlatform = "Intel
   * Skylake") OR (cpuPlatform = "Intel Broadwell") AND
   * (scheduling.automaticRestart = true) ``` If you want to use a regular
   * expression, use the `eq` (equal) or `ne` (not equal) operator against a
   * single un-parenthesized expression with or without quotes or against multiple
   * parenthesized expressions. Examples: `fieldname eq unquoted literal`
   * `fieldname eq 'single quoted literal'` `fieldname eq "double quoted literal"`
   * `(fieldname1 eq literal) (fieldname2 ne "literal")` The literal value is
   * interpreted as a regular expression using Google RE2 library syntax. The
   * literal value must match the entire field. For example, to filter for
   * instances that do not end with name "instance", you would use `name ne
   * .*instance`. You cannot combine constraints on multiple fields using regular
   * expressions.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * `maxResults`, Compute Engine returns a `nextPageToken` that can be used to
   * get the next page of results in subsequent list requests. Acceptable values
   * are `0` to `500`, inclusive. (Default: `500`)
   * @opt_param string orderBy Sorts list results by a certain order. By default,
   * results are returned in alphanumerical order based on the resource name. You
   * can also sort results in descending order based on the creation timestamp
   * using `orderBy="creationTimestamp desc"`. This sorts results based on the
   * `creationTimestamp` field in reverse chronological order (newest result
   * first). Use this to sort resources like operations so that the newest
   * operation is returned first. Currently, only sorting by `name` or
   * `creationTimestamp desc` is supported.
   * @opt_param string pageToken Specifies a page token to use. Set `pageToken` to
   * the `nextPageToken` returned by a previous list request to get the next page
   * of results.
   * @opt_param bool returnPartialSuccess Opt-in for partial success behavior
   * which provides partial results in case of failure. The default value is
   * false. For example, when partial success behavior is enabled, aggregatedList
   * for a single zone scope either returns all resources in the zone or no
   * resources, with an error code.
   * @return RegionInstanceGroupManagerList
   * @throws \Google\Service\Exception
   */
  public function listRegionInstanceGroupManagers($project, $region, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], RegionInstanceGroupManagerList::class);
  }
  /**
   * Lists all errors thrown by actions on instances for a given regional managed
   * instance group. The filter and orderBy query parameters are not supported.
   * (regionInstanceGroupManagers.listErrors)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request. This should
   * conform to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group.
   * It must be a string that meets the requirements in RFC1035, or an unsigned
   * long integer: must match regexp pattern:
   * (?:[a-z](?:[-a-z0-9]{0,61}[a-z0-9])?)|1-9{0,19}.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters resources listed in
   * the response. Most Compute resources support two types of filter expressions:
   * expressions that support regular expressions and expressions that follow API
   * improvement proposal AIP-160. These two types of filter expressions cannot be
   * mixed in one request. If you want to use AIP-160, your expression must
   * specify the field name, an operator, and the value that you want to use for
   * filtering. The value must be a string, a number, or a boolean. The operator
   * must be either `=`, `!=`, `>`, `<`, `<=`, `>=` or `:`. For example, if you
   * are filtering Compute Engine instances, you can exclude instances named
   * `example-instance` by specifying `name != example-instance`. The `:*`
   * comparison can be used to test whether a key has been defined. For example,
   * to find all objects with `owner` label use: ``` labels.owner:* ``` You can
   * also filter nested fields. For example, you could specify
   * `scheduling.automaticRestart = false` to include instances only if they are
   * not scheduled for automatic restarts. You can use filtering on nested fields
   * to filter based on resource labels. To filter on multiple expressions,
   * provide each separate expression within parentheses. For example: ```
   * (scheduling.automaticRestart = true) (cpuPlatform = "Intel Skylake") ``` By
   * default, each expression is an `AND` expression. However, you can include
   * `AND` and `OR` expressions explicitly. For example: ``` (cpuPlatform = "Intel
   * Skylake") OR (cpuPlatform = "Intel Broadwell") AND
   * (scheduling.automaticRestart = true) ``` If you want to use a regular
   * expression, use the `eq` (equal) or `ne` (not equal) operator against a
   * single un-parenthesized expression with or without quotes or against multiple
   * parenthesized expressions. Examples: `fieldname eq unquoted literal`
   * `fieldname eq 'single quoted literal'` `fieldname eq "double quoted literal"`
   * `(fieldname1 eq literal) (fieldname2 ne "literal")` The literal value is
   * interpreted as a regular expression using Google RE2 library syntax. The
   * literal value must match the entire field. For example, to filter for
   * instances that do not end with name "instance", you would use `name ne
   * .*instance`. You cannot combine constraints on multiple fields using regular
   * expressions.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * `maxResults`, Compute Engine returns a `nextPageToken` that can be used to
   * get the next page of results in subsequent list requests. Acceptable values
   * are `0` to `500`, inclusive. (Default: `500`)
   * @opt_param string orderBy Sorts list results by a certain order. By default,
   * results are returned in alphanumerical order based on the resource name. You
   * can also sort results in descending order based on the creation timestamp
   * using `orderBy="creationTimestamp desc"`. This sorts results based on the
   * `creationTimestamp` field in reverse chronological order (newest result
   * first). Use this to sort resources like operations so that the newest
   * operation is returned first. Currently, only sorting by `name` or
   * `creationTimestamp desc` is supported.
   * @opt_param string pageToken Specifies a page token to use. Set `pageToken` to
   * the `nextPageToken` returned by a previous list request to get the next page
   * of results.
   * @opt_param bool returnPartialSuccess Opt-in for partial success behavior
   * which provides partial results in case of failure. The default value is
   * false. For example, when partial success behavior is enabled, aggregatedList
   * for a single zone scope either returns all resources in the zone or no
   * resources, with an error code.
   * @return RegionInstanceGroupManagersListErrorsResponse
   * @throws \Google\Service\Exception
   */
  public function listErrors($project, $region, $instanceGroupManager, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager];
    $params = array_merge($params, $optParams);
    return $this->call('listErrors', [$params], RegionInstanceGroupManagersListErrorsResponse::class);
  }
  /**
   * Lists the instances in the managed instance group and instances that are
   * scheduled to be created. The list includes any current actions that the group
   * has scheduled for its instances. The orderBy query parameter is not
   * supported. The `pageToken` query parameter is supported only if the group's
   * `listManagedInstancesResults` field is set to `PAGINATED`.
   * (regionInstanceGroupManagers.listManagedInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager The name of the managed instance group.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters resources listed in
   * the response. Most Compute resources support two types of filter expressions:
   * expressions that support regular expressions and expressions that follow API
   * improvement proposal AIP-160. These two types of filter expressions cannot be
   * mixed in one request. If you want to use AIP-160, your expression must
   * specify the field name, an operator, and the value that you want to use for
   * filtering. The value must be a string, a number, or a boolean. The operator
   * must be either `=`, `!=`, `>`, `<`, `<=`, `>=` or `:`. For example, if you
   * are filtering Compute Engine instances, you can exclude instances named
   * `example-instance` by specifying `name != example-instance`. The `:*`
   * comparison can be used to test whether a key has been defined. For example,
   * to find all objects with `owner` label use: ``` labels.owner:* ``` You can
   * also filter nested fields. For example, you could specify
   * `scheduling.automaticRestart = false` to include instances only if they are
   * not scheduled for automatic restarts. You can use filtering on nested fields
   * to filter based on resource labels. To filter on multiple expressions,
   * provide each separate expression within parentheses. For example: ```
   * (scheduling.automaticRestart = true) (cpuPlatform = "Intel Skylake") ``` By
   * default, each expression is an `AND` expression. However, you can include
   * `AND` and `OR` expressions explicitly. For example: ``` (cpuPlatform = "Intel
   * Skylake") OR (cpuPlatform = "Intel Broadwell") AND
   * (scheduling.automaticRestart = true) ``` If you want to use a regular
   * expression, use the `eq` (equal) or `ne` (not equal) operator against a
   * single un-parenthesized expression with or without quotes or against multiple
   * parenthesized expressions. Examples: `fieldname eq unquoted literal`
   * `fieldname eq 'single quoted literal'` `fieldname eq "double quoted literal"`
   * `(fieldname1 eq literal) (fieldname2 ne "literal")` The literal value is
   * interpreted as a regular expression using Google RE2 library syntax. The
   * literal value must match the entire field. For example, to filter for
   * instances that do not end with name "instance", you would use `name ne
   * .*instance`. You cannot combine constraints on multiple fields using regular
   * expressions.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * `maxResults`, Compute Engine returns a `nextPageToken` that can be used to
   * get the next page of results in subsequent list requests. Acceptable values
   * are `0` to `500`, inclusive. (Default: `500`)
   * @opt_param string orderBy Sorts list results by a certain order. By default,
   * results are returned in alphanumerical order based on the resource name. You
   * can also sort results in descending order based on the creation timestamp
   * using `orderBy="creationTimestamp desc"`. This sorts results based on the
   * `creationTimestamp` field in reverse chronological order (newest result
   * first). Use this to sort resources like operations so that the newest
   * operation is returned first. Currently, only sorting by `name` or
   * `creationTimestamp desc` is supported.
   * @opt_param string pageToken Specifies a page token to use. Set `pageToken` to
   * the `nextPageToken` returned by a previous list request to get the next page
   * of results.
   * @opt_param bool returnPartialSuccess Opt-in for partial success behavior
   * which provides partial results in case of failure. The default value is
   * false. For example, when partial success behavior is enabled, aggregatedList
   * for a single zone scope either returns all resources in the zone or no
   * resources, with an error code.
   * @return RegionInstanceGroupManagersListInstancesResponse
   * @throws \Google\Service\Exception
   */
  public function listManagedInstances($project, $region, $instanceGroupManager, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager];
    $params = array_merge($params, $optParams);
    return $this->call('listManagedInstances', [$params], RegionInstanceGroupManagersListInstancesResponse::class);
  }
  /**
   * Lists all of the per-instance configurations defined for the managed instance
   * group. The orderBy query parameter is not supported.
   * (regionInstanceGroupManagers.listPerInstanceConfigs)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request, should conform
   * to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group.
   * It should conform to RFC1035.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter expression that filters resources listed in
   * the response. Most Compute resources support two types of filter expressions:
   * expressions that support regular expressions and expressions that follow API
   * improvement proposal AIP-160. These two types of filter expressions cannot be
   * mixed in one request. If you want to use AIP-160, your expression must
   * specify the field name, an operator, and the value that you want to use for
   * filtering. The value must be a string, a number, or a boolean. The operator
   * must be either `=`, `!=`, `>`, `<`, `<=`, `>=` or `:`. For example, if you
   * are filtering Compute Engine instances, you can exclude instances named
   * `example-instance` by specifying `name != example-instance`. The `:*`
   * comparison can be used to test whether a key has been defined. For example,
   * to find all objects with `owner` label use: ``` labels.owner:* ``` You can
   * also filter nested fields. For example, you could specify
   * `scheduling.automaticRestart = false` to include instances only if they are
   * not scheduled for automatic restarts. You can use filtering on nested fields
   * to filter based on resource labels. To filter on multiple expressions,
   * provide each separate expression within parentheses. For example: ```
   * (scheduling.automaticRestart = true) (cpuPlatform = "Intel Skylake") ``` By
   * default, each expression is an `AND` expression. However, you can include
   * `AND` and `OR` expressions explicitly. For example: ``` (cpuPlatform = "Intel
   * Skylake") OR (cpuPlatform = "Intel Broadwell") AND
   * (scheduling.automaticRestart = true) ``` If you want to use a regular
   * expression, use the `eq` (equal) or `ne` (not equal) operator against a
   * single un-parenthesized expression with or without quotes or against multiple
   * parenthesized expressions. Examples: `fieldname eq unquoted literal`
   * `fieldname eq 'single quoted literal'` `fieldname eq "double quoted literal"`
   * `(fieldname1 eq literal) (fieldname2 ne "literal")` The literal value is
   * interpreted as a regular expression using Google RE2 library syntax. The
   * literal value must match the entire field. For example, to filter for
   * instances that do not end with name "instance", you would use `name ne
   * .*instance`. You cannot combine constraints on multiple fields using regular
   * expressions.
   * @opt_param string maxResults The maximum number of results per page that
   * should be returned. If the number of available results is larger than
   * `maxResults`, Compute Engine returns a `nextPageToken` that can be used to
   * get the next page of results in subsequent list requests. Acceptable values
   * are `0` to `500`, inclusive. (Default: `500`)
   * @opt_param string orderBy Sorts list results by a certain order. By default,
   * results are returned in alphanumerical order based on the resource name. You
   * can also sort results in descending order based on the creation timestamp
   * using `orderBy="creationTimestamp desc"`. This sorts results based on the
   * `creationTimestamp` field in reverse chronological order (newest result
   * first). Use this to sort resources like operations so that the newest
   * operation is returned first. Currently, only sorting by `name` or
   * `creationTimestamp desc` is supported.
   * @opt_param string pageToken Specifies a page token to use. Set `pageToken` to
   * the `nextPageToken` returned by a previous list request to get the next page
   * of results.
   * @opt_param bool returnPartialSuccess Opt-in for partial success behavior
   * which provides partial results in case of failure. The default value is
   * false. For example, when partial success behavior is enabled, aggregatedList
   * for a single zone scope either returns all resources in the zone or no
   * resources, with an error code.
   * @return RegionInstanceGroupManagersListInstanceConfigsResp
   * @throws \Google\Service\Exception
   */
  public function listPerInstanceConfigs($project, $region, $instanceGroupManager, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager];
    $params = array_merge($params, $optParams);
    return $this->call('listPerInstanceConfigs', [$params], RegionInstanceGroupManagersListInstanceConfigsResp::class);
  }
  /**
   * Updates a managed instance group using the information that you specify in
   * the request. This operation is marked as DONE when the group is patched even
   * if the instances in the group are still in the process of being patched. You
   * must separately verify the status of the individual instances with the
   * listmanagedinstances method. This method supports PATCH semantics and uses
   * the JSON merge patch format and processing rules. If you update your group to
   * specify a new template or instance configuration, it's possible that your
   * intended specification for each VM in the group is different from the current
   * state of that VM. To learn how to apply an updated configuration to the VMs
   * in a MIG, see Updating instances in a MIG.
   * (regionInstanceGroupManagers.patch)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager The name of the instance group manager.
   * @param InstanceGroupManager $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($project, $region, $instanceGroupManager, InstanceGroupManager $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
  /**
   * Inserts or patches per-instance configurations for the managed instance
   * group. perInstanceConfig.name serves as a key used to distinguish whether to
   * perform insert or patch.
   * (regionInstanceGroupManagers.patchPerInstanceConfigs)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request, should conform
   * to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group.
   * It should conform to RFC1035.
   * @param RegionInstanceGroupManagerPatchInstanceConfigReq $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patchPerInstanceConfigs($project, $region, $instanceGroupManager, RegionInstanceGroupManagerPatchInstanceConfigReq $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patchPerInstanceConfigs', [$params], Operation::class);
  }
  /**
   * Flags the specified VM instances in the managed instance group to be
   * immediately recreated. Each instance is recreated using the group's current
   * configuration. This operation is marked as DONE when the flag is set even if
   * the instances have not yet been recreated. You must separately verify the
   * status of each instance by checking its currentAction field; for more
   * information, see Checking the status of managed instances. If the group is
   * part of a backend service that has enabled connection draining, it can take
   * up to 60 seconds after the connection draining duration has elapsed before
   * the VM instance is removed or deleted. You can specify a maximum of 1000
   * instances with this method per request.
   * (regionInstanceGroupManagers.recreateInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersRecreateRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function recreateInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersRecreateRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('recreateInstances', [$params], Operation::class);
  }
  /**
   * Changes the intended size of the managed instance group. If you increase the
   * size, the group creates new instances using the current instance template. If
   * you decrease the size, the group deletes one or more instances. The resize
   * operation is marked DONE if the resize request is successful. The underlying
   * actions take additional time. You must separately verify the status of the
   * creating or deleting actions with the listmanagedinstances method. If the
   * group is part of a backend service that has enabled connection draining, it
   * can take up to 60 seconds after the connection draining duration has elapsed
   * before the VM instance is removed or deleted.
   * (regionInstanceGroupManagers.resize)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param int $size Number of instances that should exist in this instance group
   * manager.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function resize($project, $region, $instanceGroupManager, $size, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'size' => $size];
    $params = array_merge($params, $optParams);
    return $this->call('resize', [$params], Operation::class);
  }
  /**
   * Flags the specified instances in the managed instance group to be resumed.
   * This method increases the targetSize and decreases the targetSuspendedSize of
   * the managed instance group by the number of instances that you resume. The
   * resumeInstances operation is marked DONE if the resumeInstances request is
   * successful. The underlying actions take additional time. You must separately
   * verify the status of the RESUMING action with the listmanagedinstances
   * method. In this request, you can only specify instances that are suspended.
   * For example, if an instance was previously suspended using the
   * suspendInstances method, it can be resumed using the resumeInstances method.
   * If a health check is attached to the managed instance group, the specified
   * instances will be verified as healthy after they are resumed. You can specify
   * a maximum of 1000 instances with this method per request.
   * (regionInstanceGroupManagers.resumeInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersResumeInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function resumeInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersResumeInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('resumeInstances', [$params], Operation::class);
  }
  /**
   * Sets the instance template to use when creating new instances or recreating
   * instances in this group. Existing instances are not affected.
   * (regionInstanceGroupManagers.setInstanceTemplate)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager The name of the managed instance group.
   * @param RegionInstanceGroupManagersSetTemplateRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function setInstanceTemplate($project, $region, $instanceGroupManager, RegionInstanceGroupManagersSetTemplateRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('setInstanceTemplate', [$params], Operation::class);
  }
  /**
   * Modifies the target pools to which all new instances in this group are
   * assigned. Existing instances in the group are not affected.
   * (regionInstanceGroupManagers.setTargetPools)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersSetTargetPoolsRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function setTargetPools($project, $region, $instanceGroupManager, RegionInstanceGroupManagersSetTargetPoolsRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('setTargetPools', [$params], Operation::class);
  }
  /**
   * Flags the specified instances in the managed instance group to be started.
   * This method increases the targetSize and decreases the targetStoppedSize of
   * the managed instance group by the number of instances that you start. The
   * startInstances operation is marked DONE if the startInstances request is
   * successful. The underlying actions take additional time. You must separately
   * verify the status of the STARTING action with the listmanagedinstances
   * method. In this request, you can only specify instances that are stopped. For
   * example, if an instance was previously stopped using the stopInstances
   * method, it can be started using the startInstances method. If a health check
   * is attached to the managed instance group, the specified instances will be
   * verified as healthy after they are started. You can specify a maximum of 1000
   * instances with this method per request.
   * (regionInstanceGroupManagers.startInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersStartInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function startInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersStartInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('startInstances', [$params], Operation::class);
  }
  /**
   * Flags the specified instances in the managed instance group to be immediately
   * stopped. You can only specify instances that are running in this request.
   * This method reduces the targetSize and increases the targetStoppedSize of the
   * managed instance group by the number of instances that you stop. The
   * stopInstances operation is marked DONE if the stopInstances request is
   * successful. The underlying actions take additional time. You must separately
   * verify the status of the STOPPING action with the listmanagedinstances
   * method. If the standbyPolicy.initialDelaySec field is set, the group delays
   * stopping the instances until initialDelaySec have passed from
   * instance.creationTimestamp (that is, when the instance was created). This
   * delay gives your application time to set itself up and initialize on the
   * instance. If more than initialDelaySec seconds have passed since
   * instance.creationTimestamp when this method is called, there will be zero
   * delay. If the group is part of a backend service that has enabled connection
   * draining, it can take up to 60 seconds after the connection draining duration
   * has elapsed before the VM instance is stopped. Stopped instances can be
   * started using the startInstances method. You can specify a maximum of 1000
   * instances with this method per request.
   * (regionInstanceGroupManagers.stopInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager The name of the managed instance group.
   * @param RegionInstanceGroupManagersStopInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function stopInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersStopInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('stopInstances', [$params], Operation::class);
  }
  /**
   * Flags the specified instances in the managed instance group to be immediately
   * suspended. You can only specify instances that are running in this request.
   * This method reduces the targetSize and increases the targetSuspendedSize of
   * the managed instance group by the number of instances that you suspend. The
   * suspendInstances operation is marked DONE if the suspendInstances request is
   * successful. The underlying actions take additional time. You must separately
   * verify the status of the SUSPENDING action with the listmanagedinstances
   * method. If the standbyPolicy.initialDelaySec field is set, the group delays
   * suspension of the instances until initialDelaySec have passed from
   * instance.creationTimestamp (that is, when the instance was created). This
   * delay gives your application time to set itself up and initialize on the
   * instance. If more than initialDelaySec seconds have passed since
   * instance.creationTimestamp when this method is called, there will be zero
   * delay. If the group is part of a backend service that has enabled connection
   * draining, it can take up to 60 seconds after the connection draining duration
   * has elapsed before the VM instance is suspended. Suspended instances can be
   * resumed using the resumeInstances method. You can specify a maximum of 1000
   * instances with this method per request.
   * (regionInstanceGroupManagers.suspendInstances)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request.
   * @param string $instanceGroupManager Name of the managed instance group.
   * @param RegionInstanceGroupManagersSuspendInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function suspendInstances($project, $region, $instanceGroupManager, RegionInstanceGroupManagersSuspendInstancesRequest $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('suspendInstances', [$params], Operation::class);
  }
  /**
   * Inserts or updates per-instance configurations for the managed instance
   * group. perInstanceConfig.name serves as a key used to distinguish whether to
   * perform insert or patch.
   * (regionInstanceGroupManagers.updatePerInstanceConfigs)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region scoping this request, should conform
   * to RFC1035.
   * @param string $instanceGroupManager The name of the managed instance group.
   * It should conform to RFC1035.
   * @param RegionInstanceGroupManagerUpdateInstanceConfigReq $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId An optional request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server will know to ignore the request if it has already been completed. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported (
   * 00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function updatePerInstanceConfigs($project, $region, $instanceGroupManager, RegionInstanceGroupManagerUpdateInstanceConfigReq $postBody, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region, 'instanceGroupManager' => $instanceGroupManager, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('updatePerInstanceConfigs', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RegionInstanceGroupManagers::class, 'Google_Service_Compute_Resource_RegionInstanceGroupManagers');
