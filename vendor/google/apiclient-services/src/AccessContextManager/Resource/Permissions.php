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

namespace Google\Service\AccessContextManager\Resource;

use Google\Service\AccessContextManager\ListSupportedPermissionsResponse;

/**
 * The "permissions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $accesscontextmanagerService = new Google\Service\AccessContextManager(...);
 *   $permissions = $accesscontextmanagerService->permissions;
 *  </code>
 */
class Permissions extends \Google\Service\Resource
{
  /**
   * Lists all supported permissions in VPC Service Controls ingress and egress
   * rules for Granular Controls. (permissions.listPermissions)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. This flag specifies the maximum number of
   * services to return per page. Default value is 100.
   * @opt_param string pageToken Optional. Use this token to retrieve a specific
   * page of results. Default is the first page.
   * @return ListSupportedPermissionsResponse
   * @throws \Google\Service\Exception
   */
  public function listPermissions($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListSupportedPermissionsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Permissions::class, 'Google_Service_AccessContextManager_Resource_Permissions');
