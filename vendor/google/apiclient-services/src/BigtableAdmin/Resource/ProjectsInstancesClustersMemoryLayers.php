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

namespace Google\Service\BigtableAdmin\Resource;

use Google\Service\BigtableAdmin\ListMemoryLayersResponse;

/**
 * The "memoryLayers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $bigtableadminService = new Google\Service\BigtableAdmin(...);
 *   $memoryLayers = $bigtableadminService->projects_instances_clusters_memoryLayers;
 *  </code>
 */
class ProjectsInstancesClustersMemoryLayers extends \Google\Service\Resource
{
  /**
   * Lists information about memory layers.
   * (memoryLayers.listProjectsInstancesClustersMemoryLayers)
   *
   * @param string $parent Required. The unique name of the cluster for which a
   * list of memory layers is requested. Values are of the form
   * `projects/{project}/instances/{instance}/clusters/{cluster}`. Use `{cluster}
   * = '-'` to list MemoryLayers for all Clusters in an instance, e.g.,
   * `projects/myproject/instances/myinstance/clusters/-`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. The maximum number of memory layers to
   * return. The service may return fewer than this value.
   * @opt_param string pageToken Optional. A page token, received from a previous
   * `ListMemoryLayers` call. Provide this to retrieve the subsequent page. When
   * paginating, all other parameters provided to `ListMemoryLayers` must match
   * the call that provided the page token.
   * @return ListMemoryLayersResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsInstancesClustersMemoryLayers($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListMemoryLayersResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsInstancesClustersMemoryLayers::class, 'Google_Service_BigtableAdmin_Resource_ProjectsInstancesClustersMemoryLayers');
