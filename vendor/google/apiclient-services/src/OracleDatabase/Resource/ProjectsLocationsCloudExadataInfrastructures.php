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

namespace Google\Service\OracleDatabase\Resource;

use Google\Service\OracleDatabase\CloudExadataInfrastructure;
use Google\Service\OracleDatabase\ListCloudExadataInfrastructuresResponse;
use Google\Service\OracleDatabase\Operation;

/**
 * The "cloudExadataInfrastructures" collection of methods.
 * Typical usage is:
 *  <code>
 *   $oracledatabaseService = new Google\Service\OracleDatabase(...);
 *   $cloudExadataInfrastructures = $oracledatabaseService->projects_locations_cloudExadataInfrastructures;
 *  </code>
 */
class ProjectsLocationsCloudExadataInfrastructures extends \Google\Service\Resource
{
  /**
   * Creates a new Exadata Infrastructure in a given project and location.
   * (cloudExadataInfrastructures.create)
   *
   * @param string $parent Required. The parent value for
   * CloudExadataInfrastructure in the following format:
   * projects/{project}/locations/{location}.
   * @param CloudExadataInfrastructure $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string cloudExadataInfrastructureId Required. The ID of the
   * Exadata Infrastructure to create. This value is restricted to
   * (^[a-z]([a-z0-9-]{0,61}[a-z0-9])?$) and must be a maximum of 63 characters in
   * length. The value must start with a letter and end with a letter or a number.
   * @opt_param string requestId Optional. An optional ID to identify the request.
   * This value is used to identify duplicate requests. If you make a request with
   * the same request ID and the original request is still in progress or
   * completed, the server ignores the second request. This prevents clients from
   * accidentally creating duplicate commitments. The request ID must be a valid
   * UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, CloudExadataInfrastructure $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single Exadata Infrastructure. (cloudExadataInfrastructures.delete)
   *
   * @param string $name Required. The name of the Cloud Exadata Infrastructure in
   * the following format: projects/{project}/locations/{location}/cloudExadataInf
   * rastructures/{cloud_exadata_infrastructure}.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force Optional. If set to true, all VM clusters for this
   * Exadata Infrastructure will be deleted. An Exadata Infrastructure can only be
   * deleted once all its VM clusters have been deleted.
   * @opt_param string requestId Optional. An optional ID to identify the request.
   * This value is used to identify duplicate requests. If you make a request with
   * the same request ID and the original request is still in progress or
   * completed, the server ignores the second request. This prevents clients from
   * accidentally creating duplicate commitments. The request ID must be a valid
   * UUID with the exception that zero UUID is not supported
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
   * Gets details of a single Exadata Infrastructure.
   * (cloudExadataInfrastructures.get)
   *
   * @param string $name Required. The name of the Cloud Exadata Infrastructure in
   * the following format: projects/{project}/locations/{location}/cloudExadataInf
   * rastructures/{cloud_exadata_infrastructure}.
   * @param array $optParams Optional parameters.
   * @return CloudExadataInfrastructure
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], CloudExadataInfrastructure::class);
  }
  /**
   * Lists Exadata Infrastructures in a given project and location. (cloudExadataI
   * nfrastructures.listProjectsLocationsCloudExadataInfrastructures)
   *
   * @param string $parent Required. The parent value for
   * CloudExadataInfrastructure in the following format:
   * projects/{project}/locations/{location}.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. The maximum number of items to return. If
   * unspecified, at most 50 Exadata infrastructures will be returned. The maximum
   * value is 1000; values above 1000 will be coerced to 1000.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @return ListCloudExadataInfrastructuresResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsCloudExadataInfrastructures($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListCloudExadataInfrastructuresResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsCloudExadataInfrastructures::class, 'Google_Service_OracleDatabase_Resource_ProjectsLocationsCloudExadataInfrastructures');
