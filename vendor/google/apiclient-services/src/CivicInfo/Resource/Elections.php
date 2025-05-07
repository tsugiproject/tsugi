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

namespace Google\Service\CivicInfo\Resource;

use Google\Service\CivicInfo\ElectionsQueryResponse;
use Google\Service\CivicInfo\VoterInfoResponse;

/**
 * The "elections" collection of methods.
 * Typical usage is:
 *  <code>
 *   $civicinfoService = new Google\Service\CivicInfo(...);
 *   $elections = $civicinfoService->elections;
 *  </code>
 */
class Elections extends \Google\Service\Resource
{
  /**
   * List of available elections to query. (elections.electionQuery)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool productionDataOnly Whether to include data that has not been
   * allowlisted yet
   * @return ElectionsQueryResponse
   * @throws \Google\Service\Exception
   */
  public function electionQuery($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('electionQuery', [$params], ElectionsQueryResponse::class);
  }
  /**
   * Looks up information relevant to a voter based on the voter's registered
   * address. (elections.voterInfoQuery)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string address The registered address of the voter to look up.
   * @opt_param string electionId The unique ID of the election to look up. A list
   * of election IDs can be obtained at
   * https://www.googleapis.com/civicinfo/{version}/elections. If no election ID
   * is specified in the query and there is more than one election with data for
   * the given voter, the additional elections are provided in the otherElections
   * response field.
   * @opt_param bool officialOnly If set to true, only data from official state
   * sources will be returned.
   * @opt_param bool productionDataOnly Whether to include data that has not been
   * vetted yet. Should only be made available to internal IPs or trusted
   * partners. This is a non-discoverable parameter in the One Platform API
   * config.
   * @opt_param bool returnAllAvailableData If set to true, the query will return
   * the success code and include any partial information when it is unable to
   * determine a matching address or unable to determine the election for
   * electionId=0 queries.
   * @return VoterInfoResponse
   * @throws \Google\Service\Exception
   */
  public function voterInfoQuery($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('voterInfoQuery', [$params], VoterInfoResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Elections::class, 'Google_Service_CivicInfo_Resource_Elections');
