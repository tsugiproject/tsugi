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

use Google\Service\Compute\Region;
use Google\Service\Compute\RegionList;

/**
 * The "regions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $computeService = new Google\Service\Compute(...);
 *   $regions = $computeService->regions;
 *  </code>
 */
class Regions extends \Google\Service\Resource
{
  /**
   * Returns the specified Region resource. To decrease latency for this method,
   * you can optionally omit any unneeded information from the response by using a
   * field mask. This practice is especially recommended for unused quota
   * information (the `quotas` field). To exclude one or more fields, set your
   * request's `fields` query parameter to only include the fields you need. For
   * example, to only include the `id` and `selfLink` fields, add the query
   * parameter `?fields=id,selfLink` to your request. This method fails if the
   * quota information is unavailable for the region and if the organization
   * policy constraint compute.requireBasicQuotaInResponse is enforced. This
   * constraint, when enforced, disables the fail-open behaviour when quota
   * information (the `items.quotas` field) is unavailable for the region. It is
   * recommended to use the default setting for the constraint unless your
   * application requires the fail-closed behaviour for this method. (regions.get)
   *
   * @param string $project Project ID for this request.
   * @param string $region Name of the region resource to return.
   * @param array $optParams Optional parameters.
   * @return Region
   * @throws \Google\Service\Exception
   */
  public function get($project, $region, $optParams = [])
  {
    $params = ['project' => $project, 'region' => $region];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Region::class);
  }
  /**
   * Retrieves the list of region resources available to the specified project. To
   * decrease latency for this method, you can optionally omit any unneeded
   * information from the response by using a field mask. This practice is
   * especially recommended for unused quota information (the `items.quotas`
   * field). To exclude one or more fields, set your request's `fields` query
   * parameter to only include the fields you need. For example, to only include
   * the `id` and `selfLink` fields, add the query parameter `?fields=id,selfLink`
   * to your request. This method fails if the quota information is unavailable
   * for the region and if the organization policy constraint
   * compute.requireBasicQuotaInResponse is enforced. This constraint, when
   * enforced, disables the fail-open behaviour when quota information (the
   * `items.quotas` field) is unavailable for the region. It is recommended to use
   * the default setting for the constraint unless your application requires the
   * fail-closed behaviour for this method. (regions.listRegions)
   *
   * @param string $project Project ID for this request.
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
   * @return RegionList
   * @throws \Google\Service\Exception
   */
  public function listRegions($project, $optParams = [])
  {
    $params = ['project' => $project];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], RegionList::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Regions::class, 'Google_Service_Compute_Resource_Regions');
