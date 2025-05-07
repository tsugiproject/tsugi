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

namespace Google\Service\DataprocMetastore\Resource;

use Google\Service\DataprocMetastore\ListMigrationExecutionsResponse;
use Google\Service\DataprocMetastore\MigrationExecution;
use Google\Service\DataprocMetastore\Operation;

/**
 * The "migrationExecutions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $metastoreService = new Google\Service\DataprocMetastore(...);
 *   $migrationExecutions = $metastoreService->projects_locations_services_migrationExecutions;
 *  </code>
 */
class ProjectsLocationsServicesMigrationExecutions extends \Google\Service\Resource
{
  /**
   * Deletes a single migration execution. (migrationExecutions.delete)
   *
   * @param string $name Required. The relative resource name of the
   * migrationExecution to delete, in the following form:projects/{project_number}
   * /locations/{location_id}/services/{service_id}/migrationExecutions/{migration
   * _execution_id}.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. A request ID. Specify a unique request
   * ID to allow the server to ignore the request if it has completed. The server
   * will ignore subsequent requests that provide a duplicate request ID for at
   * least 60 minutes after the first request.For example, if an initial request
   * times out, followed by another request with the same request ID, the server
   * ignores the second request to prevent the creation of duplicate
   * commitments.The request ID must be a valid UUID
   * (https://en.wikipedia.org/wiki/Universally_unique_identifier#Format) A zero
   * UUID (00000000-0000-0000-0000-000000000000) is not supported.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], Operation::class);
  }
  /**
   * Gets details of a single migration execution. (migrationExecutions.get)
   *
   * @param string $name Required. The relative resource name of the migration
   * execution to retrieve, in the following form:projects/{project_number}/locati
   * ons/{location_id}/services/{service_id}/migrationExecutions/{migration_execut
   * ion_id}.
   * @param array $optParams Optional parameters.
   * @return MigrationExecution
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], MigrationExecution::class);
  }
  /**
   * Lists migration executions on a service.
   * (migrationExecutions.listProjectsLocationsServicesMigrationExecutions)
   *
   * @param string $parent Required. The relative resource name of the service
   * whose migration executions to list, in the following form:projects/{project_n
   * umber}/locations/{location_id}/services/{service_id}/migrationExecutions.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. The filter to apply to list results.
   * @opt_param string orderBy Optional. Specify the ordering of results as
   * described in Sorting Order
   * (https://cloud.google.com/apis/design/design_patterns#sorting_order). If not
   * specified, the results will be sorted in the default order.
   * @opt_param int pageSize Optional. The maximum number of migration executions
   * to return. The response may contain less than the maximum number. If
   * unspecified, no more than 500 migration executions are returned. The maximum
   * value is 1000; values above 1000 are changed to 1000.
   * @opt_param string pageToken Optional. A page token, received from a previous
   * DataprocMetastore.ListMigrationExecutions call. Provide this token to
   * retrieve the subsequent page.To retrieve the first page, supply an empty page
   * token.When paginating, other parameters provided to
   * DataprocMetastore.ListMigrationExecutions must match the call that provided
   * the page token.
   * @return ListMigrationExecutionsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsServicesMigrationExecutions($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListMigrationExecutionsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsServicesMigrationExecutions::class, 'Google_Service_DataprocMetastore_Resource_ProjectsLocationsServicesMigrationExecutions');
