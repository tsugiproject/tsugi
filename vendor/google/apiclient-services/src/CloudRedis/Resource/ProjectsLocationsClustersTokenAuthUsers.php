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

namespace Google\Service\CloudRedis\Resource;

use Google\Service\CloudRedis\AddAuthTokenRequest;
use Google\Service\CloudRedis\ListTokenAuthUsersResponse;
use Google\Service\CloudRedis\Operation;
use Google\Service\CloudRedis\TokenAuthUser;

/**
 * The "tokenAuthUsers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $redisService = new Google\Service\CloudRedis(...);
 *   $tokenAuthUsers = $redisService->projects_locations_clusters_tokenAuthUsers;
 *  </code>
 */
class ProjectsLocationsClustersTokenAuthUsers extends \Google\Service\Resource
{
  /**
   * Adds a auth token for a user of a token based auth enabled cluster.
   * (tokenAuthUsers.addAuthToken)
   *
   * @param string $tokenAuthUser Required. The name of the token auth user
   * resource that this auth token will be added for. Format: projects/{project}/l
   * ocations/{location}/clusters/{cluster}/tokenAuthUsers/{token_auth_user}
   * @param AddAuthTokenRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function addAuthToken($tokenAuthUser, AddAuthTokenRequest $postBody, $optParams = [])
  {
    $params = ['tokenAuthUser' => $tokenAuthUser, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('addAuthToken', [$params], Operation::class);
  }
  /**
   * Deletes a token auth user for a token based auth enabled cluster.
   * (tokenAuthUsers.delete)
   *
   * @param string $name Required. The name of the token auth user to delete.
   * Format: projects/{project}/locations/{location}/clusters/{cluster}/tokenAuthU
   * sers/{token_auth_user}
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force Optional. If set to true, any child auth tokens of this
   * user will also be deleted. Otherwise, the request will only work if the user
   * has no auth tokens.
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
   * Gets a specific token auth user for a basic auth enabled cluster.
   * (tokenAuthUsers.get)
   *
   * @param string $name Required. The name of token auth user for a token based
   * auth enabled cluster. Format: projects/{project}/locations/{location}/cluster
   * s/{cluster}/tokenAuthUsers/{token_auth_user}
   * @param array $optParams Optional parameters.
   * @return TokenAuthUser
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], TokenAuthUser::class);
  }
  /**
   * Lists all the token auth users for a token based auth enabled cluster.
   * (tokenAuthUsers.listProjectsLocationsClustersTokenAuthUsers)
   *
   * @param string $parent Required. The parent resource that this token based
   * auth user will be listed for. Format:
   * projects/{project}/locations/{location}/clusters/{cluster}
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Expression for filtering results.
   * @opt_param string orderBy Optional. Sort results by a defined order.
   * @opt_param int pageSize Optional. The maximum number of items to return. If
   * not specified, a default value of 1000 will be used by the service.
   * Regardless of the page_size value, the response may include a partial list
   * and a caller should only rely on response's The maximum value is 1000; values
   * above 1000 will be coerced to 1000. `next_page_token` to determine if there
   * are more clusters left to be queried.
   * @opt_param string pageToken Optional. The `next_page_token` value returned
   * from a previous [ListTokenAuthUsers] request, if any.
   * @return ListTokenAuthUsersResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsClustersTokenAuthUsers($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListTokenAuthUsersResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsClustersTokenAuthUsers::class, 'Google_Service_CloudRedis_Resource_ProjectsLocationsClustersTokenAuthUsers');
