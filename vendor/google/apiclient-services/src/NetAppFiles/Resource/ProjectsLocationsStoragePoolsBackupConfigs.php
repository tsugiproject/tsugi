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

namespace Google\Service\NetAppFiles\Resource;

use Google\Service\NetAppFiles\ListBackupConfigsResponse;

/**
 * The "backupConfigs" collection of methods.
 * Typical usage is:
 *  <code>
 *   $netappService = new Google\Service\NetAppFiles(...);
 *   $backupConfigs = $netappService->projects_locations_storagePools_backupConfigs;
 *  </code>
 */
class ProjectsLocationsStoragePoolsBackupConfigs extends \Google\Service\Resource
{
  /**
   * Lists backup configurations for all volumes in an ONTAP-mode Storage Pool.
   * (backupConfigs.listProjectsLocationsStoragePoolsBackupConfigs)
   *
   * @param string $parent Required. The ONTAP StoragePool for which to retrieve
   * backup configuration information, in the format
   * `projects/{project}/locations/{location}/storagePools/{storage_pool}`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. The standard list filter.
   * @opt_param string orderBy Optional. Sort results. Supported values are
   * "volume_id" or ""
   * @opt_param int pageSize Optional. The maximum number of items to return. The
   * service may return fewer than this value. The maximum value is 1000; values
   * above 1000 will be coerced to 1000. If unspecified or set to 0, a default of
   * 50 will be used.
   * @opt_param string pageToken Optional. The next_page_token value to use if
   * there are additional results to retrieve for this list request.
   * @return ListBackupConfigsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsStoragePoolsBackupConfigs($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListBackupConfigsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsStoragePoolsBackupConfigs::class, 'Google_Service_NetAppFiles_Resource_ProjectsLocationsStoragePoolsBackupConfigs');
