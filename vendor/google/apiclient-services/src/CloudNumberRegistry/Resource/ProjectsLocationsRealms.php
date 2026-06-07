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

namespace Google\Service\CloudNumberRegistry\Resource;

use Google\Service\CloudNumberRegistry\ListRealmsResponse;
use Google\Service\CloudNumberRegistry\Operation;
use Google\Service\CloudNumberRegistry\Realm;

/**
 * The "realms" collection of methods.
 * Typical usage is:
 *  <code>
 *   $cloudnumberregistryService = new Google\Service\CloudNumberRegistry(...);
 *   $realms = $cloudnumberregistryService->projects_locations_realms;
 *  </code>
 */
class ProjectsLocationsRealms extends \Google\Service\Resource
{
  /**
   * Creates a new Realm in a given project and location. (realms.create)
   *
   * @param string $parent Required. Value for parent.
   * @param Realm $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string realmId Required. Id of the requesting object.
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes since the first
   * request.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, Realm $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes a single Realm. (realms.delete)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force Optional. If set to true, all associated resources will
   * be deleted.
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes after the first
   * request.
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
   * Gets details of a single Realm. (realms.get)
   *
   * @param string $name Required. Name of the resource
   * @param array $optParams Optional parameters.
   *
   * @opt_param string view Optional. The view of the Realm.
   * @return Realm
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Realm::class);
  }
  /**
   * Lists Realms in a given project and location.
   * (realms.listProjectsLocationsRealms)
   *
   * @param string $parent Required. Parent value for ListRealmsRequest
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filtering results
   * @opt_param string orderBy Optional. Hint for how to order the results
   * @opt_param int pageSize Optional. Requested page size. Server may return
   * fewer items than requested. If unspecified, server will pick an appropriate
   * default.
   * @opt_param string pageToken Optional. A token identifying a page of results
   * the server should return.
   * @opt_param string view Optional. The view of the Realm.
   * @return ListRealmsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsRealms($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListRealmsResponse::class);
  }
  /**
   * Updates the parameters of a single Realm. (realms.patch)
   *
   * @param string $name Required. Identifier. Unique name/ID of the realm
   * @param Realm $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string requestId Optional. An optional request ID to identify
   * requests. Specify a unique request ID so that if you must retry your request,
   * the server will know to ignore the request if it has already been completed.
   * The server will guarantee that for at least 60 minutes since the first
   * request.
   * @opt_param string updateMask Optional. Field mask is used to specify the
   * fields to be overwritten in the Realm resource by the update.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, Realm $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsRealms::class, 'Google_Service_CloudNumberRegistry_Resource_ProjectsLocationsRealms');
