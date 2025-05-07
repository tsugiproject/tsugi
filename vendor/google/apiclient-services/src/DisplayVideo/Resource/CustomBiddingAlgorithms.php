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

namespace Google\Service\DisplayVideo\Resource;

use Google\Service\DisplayVideo\CustomBiddingAlgorithm;
use Google\Service\DisplayVideo\CustomBiddingAlgorithmRulesRef;
use Google\Service\DisplayVideo\CustomBiddingScriptRef;
use Google\Service\DisplayVideo\ListCustomBiddingAlgorithmsResponse;

/**
 * The "customBiddingAlgorithms" collection of methods.
 * Typical usage is:
 *  <code>
 *   $displayvideoService = new Google\Service\DisplayVideo(...);
 *   $customBiddingAlgorithms = $displayvideoService->customBiddingAlgorithms;
 *  </code>
 */
class CustomBiddingAlgorithms extends \Google\Service\Resource
{
  /**
   * Creates a new custom bidding algorithm. Returns the newly created custom
   * bidding algorithm if successful. (customBiddingAlgorithms.create)
   *
   * @param CustomBiddingAlgorithm $postBody
   * @param array $optParams Optional parameters.
   * @return CustomBiddingAlgorithm
   * @throws \Google\Service\Exception
   */
  public function create(CustomBiddingAlgorithm $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], CustomBiddingAlgorithm::class);
  }
  /**
   * Gets a custom bidding algorithm. (customBiddingAlgorithms.get)
   *
   * @param string $customBiddingAlgorithmId Required. The ID of the custom
   * bidding algorithm to fetch.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string advertiserId The ID of the DV360 partner that has access to
   * the custom bidding algorithm.
   * @opt_param string partnerId The ID of the DV360 partner that has access to
   * the custom bidding algorithm.
   * @return CustomBiddingAlgorithm
   * @throws \Google\Service\Exception
   */
  public function get($customBiddingAlgorithmId, $optParams = [])
  {
    $params = ['customBiddingAlgorithmId' => $customBiddingAlgorithmId];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], CustomBiddingAlgorithm::class);
  }
  /**
   * Lists custom bidding algorithms that are accessible to the current user and
   * can be used in bidding stratgies. The order is defined by the order_by
   * parameter. (customBiddingAlgorithms.listCustomBiddingAlgorithms)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string advertiserId The ID of the DV360 advertiser that has access
   * to the custom bidding algorithm.
   * @opt_param string filter Allows filtering by custom bidding algorithm fields.
   * Supported syntax: * Filter expressions are made up of one or more
   * restrictions. * Restrictions can be combined by `AND`. A sequence of
   * restrictions implicitly uses `AND`. * A restriction has the form of `{field}
   * {operator} {value}`. * The `customBiddingAlgorithmType` field must use the
   * `EQUALS (=)` operator. * The `displayName` field must use the `HAS (:)`
   * operator. Supported fields: * `customBiddingAlgorithmType` * `displayName`
   * Examples: * All custom bidding algorithms for which the display name contains
   * "politics": `displayName:"politics"`. * All custom bidding algorithms for
   * which the type is "SCRIPT_BASED": `customBiddingAlgorithmType=SCRIPT_BASED`
   * The length of this field should be no more than 500 characters. Reference our
   * [filter `LIST` requests](/display-video/api/guides/how-tos/filters) guide for
   * more information.
   * @opt_param string orderBy Field by which to sort the list. Acceptable values
   * are: * `displayName` (default) The default sorting order is ascending. To
   * specify descending order for a field, a suffix "desc" should be added to the
   * field name. Example: `displayName desc`.
   * @opt_param int pageSize Requested page size. Must be between `1` and `200`.
   * If unspecified will default to `100`. Returns error code `INVALID_ARGUMENT`
   * if an invalid value is specified.
   * @opt_param string pageToken A token identifying a page of results the server
   * should return. Typically, this is the value of next_page_token returned from
   * the previous call to `ListCustomBiddingAlgorithms` method. If not specified,
   * the first page of results will be returned.
   * @opt_param string partnerId The ID of the DV360 partner that has access to
   * the custom bidding algorithm.
   * @return ListCustomBiddingAlgorithmsResponse
   * @throws \Google\Service\Exception
   */
  public function listCustomBiddingAlgorithms($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListCustomBiddingAlgorithmsResponse::class);
  }
  /**
   * Updates an existing custom bidding algorithm. Returns the updated custom
   * bidding algorithm if successful. *Warning*: Starting **April 1, 2025**,
   * requests updating custom bidding algorithms that are assigned to line items
   * will return an error. [Read more about this announced change](/display-
   * video/api/deprecations#features.custom_bidding_floodlight).
   * (customBiddingAlgorithms.patch)
   *
   * @param string $customBiddingAlgorithmId Output only. The unique ID of the
   * custom bidding algorithm. Assigned by the system.
   * @param CustomBiddingAlgorithm $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Required. The mask to control which fields to
   * update.
   * @return CustomBiddingAlgorithm
   * @throws \Google\Service\Exception
   */
  public function patch($customBiddingAlgorithmId, CustomBiddingAlgorithm $postBody, $optParams = [])
  {
    $params = ['customBiddingAlgorithmId' => $customBiddingAlgorithmId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], CustomBiddingAlgorithm::class);
  }
  /**
   * Creates a rules reference object for an AlgorithmRules file. The resulting
   * reference object provides a resource path where the AlgorithmRules file
   * should be uploaded. This reference object should be included when creating a
   * new CustomBiddingAlgorithmRules resource.
   * (customBiddingAlgorithms.uploadRules)
   *
   * @param string $customBiddingAlgorithmId Required. The ID of the custom
   * bidding algorithm that owns the rules resource.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string advertiserId The ID of the advertiser that owns the parent
   * custom bidding algorithm.
   * @opt_param string partnerId The ID of the partner that owns the parent custom
   * bidding algorithm.
   * @return CustomBiddingAlgorithmRulesRef
   * @throws \Google\Service\Exception
   */
  public function uploadRules($customBiddingAlgorithmId, $optParams = [])
  {
    $params = ['customBiddingAlgorithmId' => $customBiddingAlgorithmId];
    $params = array_merge($params, $optParams);
    return $this->call('uploadRules', [$params], CustomBiddingAlgorithmRulesRef::class);
  }
  /**
   * Creates a custom bidding script reference object for a script file. The
   * resulting reference object provides a resource path to which the script file
   * should be uploaded. This reference object should be included in when creating
   * a new custom bidding script object. (customBiddingAlgorithms.uploadScript)
   *
   * @param string $customBiddingAlgorithmId Required. The ID of the custom
   * bidding algorithm owns the script.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string advertiserId The ID of the advertiser that owns the parent
   * custom bidding algorithm.
   * @opt_param string partnerId The ID of the partner that owns the parent custom
   * bidding algorithm. Only this partner will have write access to this custom
   * bidding script.
   * @return CustomBiddingScriptRef
   * @throws \Google\Service\Exception
   */
  public function uploadScript($customBiddingAlgorithmId, $optParams = [])
  {
    $params = ['customBiddingAlgorithmId' => $customBiddingAlgorithmId];
    $params = array_merge($params, $optParams);
    return $this->call('uploadScript', [$params], CustomBiddingScriptRef::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CustomBiddingAlgorithms::class, 'Google_Service_DisplayVideo_Resource_CustomBiddingAlgorithms');
