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

namespace Google\Service\PostmasterTools\Resource;

use Google\Service\PostmasterTools\BatchQueryDomainStatsRequest;
use Google\Service\PostmasterTools\BatchQueryDomainStatsResponse;

/**
 * The "domainStats" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gmailpostmastertoolsService = new Google\Service\PostmasterTools(...);
 *   $domainStats = $gmailpostmastertoolsService->domainStats;
 *  </code>
 */
class DomainStats extends \Google\Service\Resource
{
  /**
   * Executes a batch of QueryDomainStats requests for multiple domains. Returns
   * PERMISSION_DENIED if you don't have permission to access DomainStats for any
   * of the requested domains. (domainStats.batchQuery)
   *
   * @param BatchQueryDomainStatsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return BatchQueryDomainStatsResponse
   * @throws \Google\Service\Exception
   */
  public function batchQuery(BatchQueryDomainStatsRequest $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('batchQuery', [$params], BatchQueryDomainStatsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DomainStats::class, 'Google_Service_PostmasterTools_Resource_DomainStats');
