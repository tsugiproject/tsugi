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

namespace Google\Service\Backupdr\Resource;

use Google\Service\Backupdr\BackupPlanAssociation;
use Google\Service\Backupdr\ListBackupPlanAssociationsResponse;
use Google\Service\Backupdr\Operation;
use Google\Service\Backupdr\TriggerBackupRequest;

/**
 * The "backupPlanAssociations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $backupdrService = new Google\Service\Backupdr(...);
 *   $backupPlanAssociations = $backupdrService->projects_locations_backupPlanAssociations;
 *  </code>
 */
class ProjectsLocationsBackupPlanAssociations extends \Google\Service\Resource
{
  /**
   * Create a BackupPlanAssociation (backupPlanAssociations.create)
   *
   * @param string $parent Required. The backup plan association project and
   * location in the format `projects/{project_id}/locations/{location}`. In Cloud
   * BackupDR locations map to GCP regions, for example **us-central1**.
   * @param BackupPlanAssociation $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string backupPlanAssociationId Required. The name of the backup
   * plan association to create. The name must be unique for the specified project
   * and location.
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes since the first
   * request. For example, consider a situation where you make an initial request
   * and t he request times out. If you make the request again with the same
   * request ID, the server can check if original operation with the same request
   * ID was received, and if so, will ignore the second request. This prevents
   * clients from accidentally creating duplicate commitments. The request ID must
   * be a valid UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, BackupPlanAssociation $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single BackupPlanAssociation. (backupPlanAssociations.delete)
   *
   * @param string $name Required. Name of the backup plan association resource,
   * in the format `projects/{project}/locations/{location}/backupPlanAssociations
   * /{backupPlanAssociationId}`
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes after the first
   * request. For example, consider a situation where you make an initial request
   * and the request times out. If you make the request again with the same
   * request ID, the server can check if original operation with the same request
   * ID was received, and if so, will ignore the second request. This prevents
   * clients from accidentally creating duplicate commitments. The request ID must
   * be a valid UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
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
   * Gets details of a single BackupPlanAssociation. (backupPlanAssociations.get)
   *
   * @param string $name Required. Name of the backup plan association resource,
   * in the format `projects/{project}/locations/{location}/backupPlanAssociations
   * /{backupPlanAssociationId}`
   * @param array $optParams Optional parameters.
   * @return BackupPlanAssociation
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], BackupPlanAssociation::class);
  }
  /**
   * Lists BackupPlanAssociations in a given project and location.
   * (backupPlanAssociations.listProjectsLocationsBackupPlanAssociations)
   *
   * @param string $parent Required. The project and location for which to
   * retrieve backup Plan Associations information, in the format
   * `projects/{project_id}/locations/{location}`. In Cloud BackupDR, locations
   * map to GCP regions, for example **us-central1**. To retrieve backup plan
   * associations for all locations, use "-" for the `{location}` value.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filtering results
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListBackupPlanAssociationsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsBackupPlanAssociations($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListBackupPlanAssociationsResponse::class);
  }
  /**
   * Triggers a new Backup. (backupPlanAssociations.triggerBackup)
   *
   * @param string $name Required. Name of the backup plan association resource,
   * in the format `projects/{project}/locations/{location}/backupPlanAssociations
   * /{backupPlanAssociationId}`
   * @param TriggerBackupRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function triggerBackup($name, TriggerBackupRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('triggerBackup', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsBackupPlanAssociations::class, 'Google_Service_Backupdr_Resource_ProjectsLocationsBackupPlanAssociations');
