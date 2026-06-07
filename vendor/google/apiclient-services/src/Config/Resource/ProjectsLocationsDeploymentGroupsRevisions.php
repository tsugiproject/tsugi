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

namespace Google\Service\Config\Resource;

use Google\Service\Config\DeploymentGroupRevision;
use Google\Service\Config\ListDeploymentGroupRevisionsResponse;

/**
 * The "revisions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $configService = new Google\Service\Config(...);
 *   $revisions = $configService->projects_locations_deploymentGroups_revisions;
 *  </code>
 */
class ProjectsLocationsDeploymentGroupsRevisions extends \Google\Service\Resource
{
  /**
   * Gets details about a DeploymentGroupRevision. (revisions.get)
   *
   * @param string $name Required. The name of the deployment group revision to
   * retrieve. Format: 'projects/{project_id}/locations/{location}/deploymentGroup
   * s/{deployment_group}/revisions/{revision}'.
   * @param array $optParams Optional parameters.
   * @return DeploymentGroupRevision
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], DeploymentGroupRevision::class);
  }
  /**
   * Lists DeploymentGroupRevisions in a given DeploymentGroup.
   * (revisions.listProjectsLocationsDeploymentGroupsRevisions)
   *
   * @param string $parent Required. The parent, which owns this collection of
   * deployment group revisions. Format: 'projects/{project_id}/locations/{locatio
   * n}/deploymentGroups/{deployment_group}'.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. When requesting a page of resources,
   * 'page_size' specifies number of resources to return. If unspecified, a
   * sensible default will be used by the server. The maximum value is 1000;
   * values above 1000 will be coerced to 1000.
   * @opt_param string pageToken Optional. Token returned by previous call to
   * 'ListDeploymentGroupRevisions' which specifies the position in the list from
   * where to continue listing the deployment group revisions. All other
   * parameters provided to `ListDeploymentGroupRevisions` must match the call
   * that provided the page token.
   * @return ListDeploymentGroupRevisionsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsDeploymentGroupsRevisions($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListDeploymentGroupRevisionsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDeploymentGroupsRevisions::class, 'Google_Service_Config_Resource_ProjectsLocationsDeploymentGroupsRevisions');
