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

namespace Google\Service\Monitoring\Resource;

use Google\Service\Monitoring\AlertPolicy;
use Google\Service\Monitoring\ListAlertPoliciesResponse;
use Google\Service\Monitoring\MonitoringEmpty;

/**
 * The "alertPolicies" collection of methods.
 * Typical usage is:
 *  <code>
 *   $monitoringService = new Google\Service\Monitoring(...);
 *   $alertPolicies = $monitoringService->projects_alertPolicies;
 *  </code>
 */
class ProjectsAlertPolicies extends \Google\Service\Resource
{
  /**
   * Creates a new alerting policy.Design your application to single-thread API
   * calls that modify the state of alerting policies in a single project. This
   * includes calls to CreateAlertPolicy, DeleteAlertPolicy and UpdateAlertPolicy.
   * (alertPolicies.create)
   *
   * @param string $name Required. The project
   * (https://cloud.google.com/monitoring/api/v3#project_name) in which to create
   * the alerting policy. The format is: projects/[PROJECT_ID_OR_NUMBER] Note that
   * this field names the parent container in which the alerting policy will be
   * written, not the name of the created policy. |name| must be a host project of
   * a Metrics Scope, otherwise INVALID_ARGUMENT error will return. The alerting
   * policy that is returned will have a name that contains a normalized
   * representation of this name as a prefix but adds a suffix of the form
   * /alertPolicies/[ALERT_POLICY_ID], identifying the policy in the container.
   * @param AlertPolicy $postBody
   * @param array $optParams Optional parameters.
   * @return AlertPolicy
   * @throws \Google\Service\Exception
   */
  public function create($name, AlertPolicy $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], AlertPolicy::class);
  }
  /**
   * Deletes an alerting policy.Design your application to single-thread API calls
   * that modify the state of alerting policies in a single project. This includes
   * calls to CreateAlertPolicy, DeleteAlertPolicy and UpdateAlertPolicy.
   * (alertPolicies.delete)
   *
   * @param string $name Required. The alerting policy to delete. The format is:
   * projects/[PROJECT_ID_OR_NUMBER]/alertPolicies/[ALERT_POLICY_ID] For more
   * information, see AlertPolicy.
   * @param array $optParams Optional parameters.
   * @return MonitoringEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], MonitoringEmpty::class);
  }
  /**
   * Gets a single alerting policy. (alertPolicies.get)
   *
   * @param string $name Required. The alerting policy to retrieve. The format is:
   * projects/[PROJECT_ID_OR_NUMBER]/alertPolicies/[ALERT_POLICY_ID]
   * @param array $optParams Optional parameters.
   * @return AlertPolicy
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], AlertPolicy::class);
  }
  /**
   * Lists the existing alerting policies for the workspace.
   * (alertPolicies.listProjectsAlertPolicies)
   *
   * @param string $name Required. The project
   * (https://cloud.google.com/monitoring/api/v3#project_name) whose alert
   * policies are to be listed. The format is: projects/[PROJECT_ID_OR_NUMBER]
   * Note that this field names the parent container in which the alerting
   * policies to be listed are stored. To retrieve a single alerting policy by
   * name, use the GetAlertPolicy operation, instead.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. If provided, this field specifies the
   * criteria that must be met by alert policies to be included in the
   * response.For more details, see sorting and filtering
   * (https://cloud.google.com/monitoring/api/v3/sorting-and-filtering).
   * @opt_param string orderBy Optional. A comma-separated list of fields by which
   * to sort the result. Supports the same set of field references as the filter
   * field. Entries can be prefixed with a minus sign to sort by the field in
   * descending order.For more details, see sorting and filtering
   * (https://cloud.google.com/monitoring/api/v3/sorting-and-filtering).
   * @opt_param int pageSize Optional. The maximum number of results to return in
   * a single response.
   * @opt_param string pageToken Optional. If this field is not empty then it must
   * contain the nextPageToken value returned by a previous call to this method.
   * Using this field causes the method to return more results from the previous
   * method call.
   * @return ListAlertPoliciesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsAlertPolicies($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListAlertPoliciesResponse::class);
  }
  /**
   * Updates an alerting policy. You can either replace the entire policy with a
   * new one or replace only certain fields in the current alerting policy by
   * specifying the fields to be updated via updateMask. Returns the updated
   * alerting policy.Design your application to single-thread API calls that
   * modify the state of alerting policies in a single project. This includes
   * calls to CreateAlertPolicy, DeleteAlertPolicy and UpdateAlertPolicy.
   * (alertPolicies.patch)
   *
   * @param string $name Identifier. Required if the policy exists. The resource
   * name for this policy. The format is:
   * projects/[PROJECT_ID_OR_NUMBER]/alertPolicies/[ALERT_POLICY_ID]
   * [ALERT_POLICY_ID] is assigned by Cloud Monitoring when the policy is created.
   * When calling the alertPolicies.create method, do not include the name field
   * in the alerting policy passed as part of the request.
   * @param AlertPolicy $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Optional. A list of alerting policy field names.
   * If this field is not empty, each listed field in the existing alerting policy
   * is set to the value of the corresponding field in the supplied policy
   * (alert_policy), or to the field's default value if the field is not in the
   * supplied alerting policy. Fields not listed retain their previous
   * value.Examples of valid field masks include display_name, documentation,
   * documentation.content, documentation.mime_type, user_labels,
   * user_label.nameofkey, enabled, conditions, combiner, etc.If this field is
   * empty, then the supplied alerting policy replaces the existing policy. It is
   * the same as deleting the existing policy and adding the supplied policy,
   * except for the following: The new policy will have the same [ALERT_POLICY_ID]
   * as the former policy. This gives you continuity with the former policy in
   * your notifications and incidents. Conditions in the new policy will keep
   * their former [CONDITION_ID] if the supplied condition includes the name field
   * with that [CONDITION_ID]. If the supplied condition omits the name field,
   * then a new [CONDITION_ID] is created.
   * @return AlertPolicy
   * @throws \Google\Service\Exception
   */
  public function patch($name, AlertPolicy $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], AlertPolicy::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsAlertPolicies::class, 'Google_Service_Monitoring_Resource_ProjectsAlertPolicies');
