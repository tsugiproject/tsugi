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

namespace Google\Service\Clouderrorreporting\Resource;

use Google\Service\Clouderrorreporting\ListGroupStatsResponse;

/**
 * The "groupStats" collection of methods.
 * Typical usage is:
 *  <code>
 *   $clouderrorreportingService = new Google\Service\Clouderrorreporting(...);
 *   $groupStats = $clouderrorreportingService->projects_groupStats;
 *  </code>
 */
class ProjectsGroupStats extends \Google\Service\Resource
{
  /**
   * Lists the specified groups. (groupStats.listProjectsGroupStats)
   *
   * @param string $projectName Required. The resource name of the Google Cloud
   * Platform project. Written as `projects/{projectID}` or
   * `projects/{projectNumber}`, where `{projectID}` and `{projectNumber}` can be
   * found in the [Google Cloud
   * console](https://support.google.com/cloud/answer/6158840). It may also
   * include a location, such as `projects/{projectID}/locations/{location}` where
   * `{location}` is a cloud region. Examples: `projects/my-project-123`,
   * `projects/5551234`, `projects/my-project-123/locations/us-central1`,
   * `projects/5551234/locations/us-central1`. For a list of supported locations,
   * see [Supported Regions](https://cloud.google.com/logging/docs/region-
   * support). `global` is the default when unspecified. Use `-` as a wildcard to
   * request group stats from all regions.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string alignment Optional. The alignment of the timed counts to be
   * returned. Default is `ALIGNMENT_EQUAL_AT_END`.
   * @opt_param string alignmentTime Optional. Time where the timed counts shall
   * be aligned if rounded alignment is chosen. Default is 00:00 UTC.
   * @opt_param string groupId Optional. List all ErrorGroupStats with these IDs.
   * The `group_id` is a unique identifier for a particular error group. The
   * identifier is derived from key parts of the error-log content and is treated
   * as Service Data. For information about how Service Data is handled, see
   * [Google Cloud Privacy Notice] (https://cloud.google.com/terms/cloud-privacy-
   * notice).
   * @opt_param string order Optional. The sort order in which the results are
   * returned. Default is `COUNT_DESC`.
   * @opt_param int pageSize Optional. The maximum number of results to return per
   * response. Default is 20.
   * @opt_param string pageToken Optional. A next_page_token provided by a
   * previous response. To view additional results, pass this token along with the
   * identical query parameters as the first request.
   * @opt_param string serviceFilter.resourceType Optional. The exact value to
   * match against [`ServiceContext.resource_type`](/error-
   * reporting/reference/rest/v1beta1/ServiceContext#FIELDS.resource_type).
   * @opt_param string serviceFilter.service Optional. The exact value to match
   * against [`ServiceContext.service`](/error-
   * reporting/reference/rest/v1beta1/ServiceContext#FIELDS.service).
   * @opt_param string serviceFilter.version Optional. The exact value to match
   * against [`ServiceContext.version`](/error-
   * reporting/reference/rest/v1beta1/ServiceContext#FIELDS.version).
   * @opt_param string timeRange.period Restricts the query to the specified time
   * range.
   * @opt_param string timedCountDuration Optional. The preferred duration for a
   * single returned TimedCount. If not set, no timed counts are returned.
   * @return ListGroupStatsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsGroupStats($projectName, $optParams = [])
  {
    $params = ['projectName' => $projectName];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListGroupStatsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsGroupStats::class, 'Google_Service_Clouderrorreporting_Resource_ProjectsGroupStats');
