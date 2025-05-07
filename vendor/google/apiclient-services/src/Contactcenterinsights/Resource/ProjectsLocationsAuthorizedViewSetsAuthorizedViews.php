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

namespace Google\Service\Contactcenterinsights\Resource;

use Google\Service\Contactcenterinsights\GoogleCloudContactcenterinsightsV1QueryMetricsRequest;
use Google\Service\Contactcenterinsights\GoogleLongrunningOperation;

/**
 * The "authorizedViews" collection of methods.
 * Typical usage is:
 *  <code>
 *   $contactcenterinsightsService = new Google\Service\Contactcenterinsights(...);
 *   $authorizedViews = $contactcenterinsightsService->projects_locations_authorizedViewSets_authorizedViews;
 *  </code>
 */
class ProjectsLocationsAuthorizedViewSetsAuthorizedViews extends \Google\Service\Resource
{
  /**
   * Query metrics. (authorizedViews.queryMetrics)
   *
   * @param string $location Required. The location of the data.
   * "projects/{project}/locations/{location}"
   * @param GoogleCloudContactcenterinsightsV1QueryMetricsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function queryMetrics($location, GoogleCloudContactcenterinsightsV1QueryMetricsRequest $postBody, $optParams = [])
  {
    $params = ['location' => $location, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('queryMetrics', [$params], GoogleLongrunningOperation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsAuthorizedViewSetsAuthorizedViews::class, 'Google_Service_Contactcenterinsights_Resource_ProjectsLocationsAuthorizedViewSetsAuthorizedViews');
