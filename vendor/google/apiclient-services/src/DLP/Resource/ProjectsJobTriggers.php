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

namespace Google\Service\DLP\Resource;

use Google\Service\DLP\GooglePrivacyDlpV2ActivateJobTriggerRequest;
use Google\Service\DLP\GooglePrivacyDlpV2CreateJobTriggerRequest;
use Google\Service\DLP\GooglePrivacyDlpV2DlpJob;
use Google\Service\DLP\GooglePrivacyDlpV2JobTrigger;
use Google\Service\DLP\GooglePrivacyDlpV2ListJobTriggersResponse;
use Google\Service\DLP\GooglePrivacyDlpV2UpdateJobTriggerRequest;
use Google\Service\DLP\GoogleProtobufEmpty;

/**
 * The "jobTriggers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dlpService = new Google\Service\DLP(...);
 *   $jobTriggers = $dlpService->projects_jobTriggers;
 *  </code>
 */
class ProjectsJobTriggers extends \Google\Service\Resource
{
  /**
   * Activate a job trigger. Causes the immediate execute of a trigger instead of
   * waiting on the trigger event to occur. (jobTriggers.activate)
   *
   * @param string $name Required. Resource name of the trigger to activate, for
   * example `projects/dlp-test-project/jobTriggers/53234423`.
   * @param GooglePrivacyDlpV2ActivateJobTriggerRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GooglePrivacyDlpV2DlpJob
   * @throws \Google\Service\Exception
   */
  public function activate($name, GooglePrivacyDlpV2ActivateJobTriggerRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('activate', [$params], GooglePrivacyDlpV2DlpJob::class);
  }
  /**
   * Creates a job trigger to run DLP actions such as scanning storage for
   * sensitive information on a set schedule. See
   * https://cloud.google.com/sensitive-data-protection/docs/creating-job-triggers
   * to learn more. (jobTriggers.create)
   *
   * @param string $parent Required. Parent resource name. The format of this
   * value varies depending on whether you have [specified a processing
   * location](https://cloud.google.com/sensitive-data-protection/docs/specifying-
   * location): + Projects scope, location specified:
   * `projects/{project_id}/locations/{location_id}` + Projects scope, no location
   * specified (defaults to global): `projects/{project_id}` The following example
   * `parent` string specifies a parent project with the identifier `example-
   * project`, and specifies the `europe-west3` location for processing data:
   * parent=projects/example-project/locations/europe-west3
   * @param GooglePrivacyDlpV2CreateJobTriggerRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GooglePrivacyDlpV2JobTrigger
   * @throws \Google\Service\Exception
   */
  public function create($parent, GooglePrivacyDlpV2CreateJobTriggerRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], GooglePrivacyDlpV2JobTrigger::class);
  }
  /**
   * Deletes a job trigger. See https://cloud.google.com/sensitive-data-
   * protection/docs/creating-job-triggers to learn more. (jobTriggers.delete)
   *
   * @param string $name Required. Resource name of the project and the
   * triggeredJob, for example `projects/dlp-test-project/jobTriggers/53234423`.
   * @param array $optParams Optional parameters.
   * @return GoogleProtobufEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], GoogleProtobufEmpty::class);
  }
  /**
   * Gets a job trigger. See https://cloud.google.com/sensitive-data-
   * protection/docs/creating-job-triggers to learn more. (jobTriggers.get)
   *
   * @param string $name Required. Resource name of the project and the
   * triggeredJob, for example `projects/dlp-test-project/jobTriggers/53234423`.
   * @param array $optParams Optional parameters.
   * @return GooglePrivacyDlpV2JobTrigger
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], GooglePrivacyDlpV2JobTrigger::class);
  }
  /**
   * Lists job triggers. See https://cloud.google.com/sensitive-data-
   * protection/docs/creating-job-triggers to learn more.
   * (jobTriggers.listProjectsJobTriggers)
   *
   * @param string $parent Required. Parent resource name. The format of this
   * value varies depending on whether you have [specified a processing
   * location](https://cloud.google.com/sensitive-data-protection/docs/specifying-
   * location): + Projects scope, location specified:
   * `projects/{project_id}/locations/{location_id}` + Projects scope, no location
   * specified (defaults to global): `projects/{project_id}` The following example
   * `parent` string specifies a parent project with the identifier `example-
   * project`, and specifies the `europe-west3` location for processing data:
   * parent=projects/example-project/locations/europe-west3
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Allows filtering. Supported syntax: * Filter
   * expressions are made up of one or more restrictions. * Restrictions can be
   * combined by `AND` or `OR` logical operators. A sequence of restrictions
   * implicitly uses `AND`. * A restriction has the form of `{field} {operator}
   * {value}`. * Supported fields/values for inspect triggers: - `status` -
   * HEALTHY|PAUSED|CANCELLED - `inspected_storage` -
   * DATASTORE|CLOUD_STORAGE|BIGQUERY - 'last_run_time` - RFC 3339 formatted
   * timestamp, surrounded by quotation marks. Nanoseconds are ignored. -
   * 'error_count' - Number of errors that have occurred while running. * The
   * operator must be `=` or `!=` for status and inspected_storage. Examples: *
   * inspected_storage = cloud_storage AND status = HEALTHY * inspected_storage =
   * cloud_storage OR inspected_storage = bigquery * inspected_storage =
   * cloud_storage AND (state = PAUSED OR state = HEALTHY) * last_run_time >
   * \"2017-12-12T00:00:00+00:00\" The length of this field should be no more than
   * 500 characters.
   * @opt_param string locationId Deprecated. This field has no effect.
   * @opt_param string orderBy Comma-separated list of triggeredJob fields to
   * order by, followed by `asc` or `desc` postfix. This list is case insensitive.
   * The default sorting order is ascending. Redundant space characters are
   * insignificant. Example: `name asc,update_time, create_time desc` Supported
   * fields are: - `create_time`: corresponds to the time the JobTrigger was
   * created. - `update_time`: corresponds to the time the JobTrigger was last
   * updated. - `last_run_time`: corresponds to the last time the JobTrigger ran.
   * - `name`: corresponds to the JobTrigger's name. - `display_name`: corresponds
   * to the JobTrigger's display name. - `status`: corresponds to JobTrigger's
   * status.
   * @opt_param int pageSize Size of the page. This value can be limited by a
   * server.
   * @opt_param string pageToken Page token to continue retrieval. Comes from the
   * previous call to ListJobTriggers. `order_by` field must not change for
   * subsequent calls.
   * @opt_param string type The type of jobs. Will use `DlpJobType.INSPECT` if not
   * set.
   * @return GooglePrivacyDlpV2ListJobTriggersResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsJobTriggers($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], GooglePrivacyDlpV2ListJobTriggersResponse::class);
  }
  /**
   * Updates a job trigger. See https://cloud.google.com/sensitive-data-
   * protection/docs/creating-job-triggers to learn more. (jobTriggers.patch)
   *
   * @param string $name Required. Resource name of the project and the
   * triggeredJob, for example `projects/dlp-test-project/jobTriggers/53234423`.
   * @param GooglePrivacyDlpV2UpdateJobTriggerRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GooglePrivacyDlpV2JobTrigger
   * @throws \Google\Service\Exception
   */
  public function patch($name, GooglePrivacyDlpV2UpdateJobTriggerRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], GooglePrivacyDlpV2JobTrigger::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsJobTriggers::class, 'Google_Service_DLP_Resource_ProjectsJobTriggers');
