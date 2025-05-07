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

namespace Google\Service\Spanner\Resource;

use Google\Service\Spanner\ListInstanceConfigOperationsResponse;

/**
 * The "instanceConfigOperations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $spannerService = new Google\Service\Spanner(...);
 *   $instanceConfigOperations = $spannerService->projects_instanceConfigOperations;
 *  </code>
 */
class ProjectsInstanceConfigOperations extends \Google\Service\Resource
{
  /**
   * Lists the user-managed instance configuration long-running operations in the
   * given project. An instance configuration operation has a name of the form
   * `projects//instanceConfigs//operations/`. The long-running operation metadata
   * field type `metadata.type_url` describes the type of the metadata. Operations
   * returned include those that have completed/failed/canceled within the last 7
   * days, and pending operations. Operations returned are ordered by
   * `operation.metadata.value.start_time` in descending order starting from the
   * most recently started operation.
   * (instanceConfigOperations.listProjectsInstanceConfigOperations)
   *
   * @param string $parent Required. The project of the instance configuration
   * operations. Values are of the form `projects/`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter An expression that filters the list of returned
   * operations. A filter expression consists of a field name, a comparison
   * operator, and a value for filtering. The value must be a string, a number, or
   * a boolean. The comparison operator must be one of: `<`, `>`, `<=`, `>=`,
   * `!=`, `=`, or `:`. Colon `:` is the contains operator. Filter rules are not
   * case sensitive. The following fields in the Operation are eligible for
   * filtering: * `name` - The name of the long-running operation * `done` - False
   * if the operation is in progress, else true. * `metadata.@type` - the type of
   * metadata. For example, the type string for CreateInstanceConfigMetadata is `t
   * ype.googleapis.com/google.spanner.admin.instance.v1.CreateInstanceConfigMetad
   * ata`. * `metadata.` - any field in metadata.value. `metadata.@type` must be
   * specified first, if filtering on metadata fields. * `error` - Error
   * associated with the long-running operation. * `response.@type` - the type of
   * response. * `response.` - any field in response.value. You can combine
   * multiple expressions by enclosing each expression in parentheses. By default,
   * expressions are combined with AND logic. However, you can specify AND, OR,
   * and NOT logic explicitly. Here are a few examples: * `done:true` - The
   * operation is complete. * `(metadata.@type=` \ `type.googleapis.com/google.spa
   * nner.admin.instance.v1.CreateInstanceConfigMetadata) AND` \
   * `(metadata.instance_config.name:custom-config) AND` \
   * `(metadata.progress.start_time < \"2021-03-28T14:50:00Z\") AND` \ `(error:*)`
   * - Return operations where: * The operation's metadata type is
   * CreateInstanceConfigMetadata. * The instance configuration name contains
   * "custom-config". * The operation started before 2021-03-28T14:50:00Z. * The
   * operation resulted in an error.
   * @opt_param int pageSize Number of operations to be returned in the response.
   * If 0 or less, defaults to the server's maximum allowed page size.
   * @opt_param string pageToken If non-empty, `page_token` should contain a
   * next_page_token from a previous ListInstanceConfigOperationsResponse to the
   * same `parent` and with the same `filter`.
   * @return ListInstanceConfigOperationsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsInstanceConfigOperations($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListInstanceConfigOperationsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsInstanceConfigOperations::class, 'Google_Service_Spanner_Resource_ProjectsInstanceConfigOperations');
