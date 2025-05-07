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

use Google\Service\Config\ListTerraformVersionsResponse;
use Google\Service\Config\TerraformVersion;

/**
 * The "terraformVersions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $configService = new Google\Service\Config(...);
 *   $terraformVersions = $configService->projects_locations_terraformVersions;
 *  </code>
 */
class ProjectsLocationsTerraformVersions extends \Google\Service\Resource
{
  /**
   * Gets details about a TerraformVersion. (terraformVersions.get)
   *
   * @param string $name Required. The name of the TerraformVersion. Format: 'proj
   * ects/{project_id}/locations/{location}/terraformVersions/{terraform_version}'
   * @param array $optParams Optional parameters.
   * @return TerraformVersion
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], TerraformVersion::class);
  }
  /**
   * Lists TerraformVersions in a given project and location.
   * (terraformVersions.listProjectsLocationsTerraformVersions)
   *
   * @param string $parent Required. The parent in whose context the
   * TerraformVersions are listed. The parent value is in the format:
   * 'projects/{project_id}/locations/{location}'.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Lists the TerraformVersions that match the
   * filter expression. A filter expression filters the resources listed in the
   * response. The expression must be of the form '{field} {operator} {value}'
   * where operators: '<', '>', '<=', '>=', '!=', '=', ':' are supported (colon
   * ':' represents a HAS operator which is roughly synonymous with equality).
   * {field} can refer to a proto or JSON field, or a synthetic field. Field names
   * can be camelCase or snake_case.
   * @opt_param string orderBy Optional. Field to use to sort the list.
   * @opt_param int pageSize Optional. When requesting a page of terraform
   * versions, 'page_size' specifies number of terraform versions to return. If
   * unspecified, at most 500 will be returned. The maximum value is 1000.
   * @opt_param string pageToken Optional. Token returned by previous call to
   * 'ListTerraformVersions' which specifies the position in the list from where
   * to continue listing the terraform versions.
   * @return ListTerraformVersionsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsTerraformVersions($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListTerraformVersionsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsTerraformVersions::class, 'Google_Service_Config_Resource_ProjectsLocationsTerraformVersions');
