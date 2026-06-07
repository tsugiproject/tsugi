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

use Google\Service\PostmasterTools\QueryDomainStatsRequest;
use Google\Service\PostmasterTools\QueryDomainStatsResponse;

/**
 * The "domainStats" collection of methods.
 * Typical usage is:
 *  <code>
 *   $gmailpostmastertoolsService = new Google\Service\PostmasterTools(...);
 *   $domainStats = $gmailpostmastertoolsService->domains_domainStats;
 *  </code>
 */
class DomainsDomainStats extends \Google\Service\Resource
{
  /**
   * Retrieves a list of domain statistics for a given domain and time period.
   * Returns statistics only for dates where data is available. Returns
   * PERMISSION_DENIED if you don't have permission to access DomainStats for the
   * domain. (domainStats.query)
   *
   * @param string $parent Required. The parent resource name where the stats are
   * queried. Format: domains/{domain}
   * @param QueryDomainStatsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return QueryDomainStatsResponse
   * @throws \Google\Service\Exception
   */
  public function query($parent, QueryDomainStatsRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('query', [$params], QueryDomainStatsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DomainsDomainStats::class, 'Google_Service_PostmasterTools_Resource_DomainsDomainStats');
