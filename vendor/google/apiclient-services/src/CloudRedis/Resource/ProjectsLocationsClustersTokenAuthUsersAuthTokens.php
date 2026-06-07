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

use Google\Service\CloudRedis\AuthToken;
use Google\Service\CloudRedis\ListAuthTokensResponse;
use Google\Service\CloudRedis\Operation;

/**
 * The "authTokens" collection of methods.
 * Typical usage is:
 *  <code>
 *   $redisService = new Google\Service\CloudRedis(...);
 *   $authTokens = $redisService->projects_locations_clusters_tokenAuthUsers_authTokens;
 *  </code>
 */
class ProjectsLocationsClustersTokenAuthUsersAuthTokens extends \Google\Service\Resource
{
  /**
   * Removes a auth token for a user of a token based auth enabled instance.
   * (authTokens.delete)
   *
   * @param string $name Required. The name of the token auth user resource that
   * this auth token will be deleted from. Format: projects/{project}/locations/{l
   * ocation}/clusters/{cluster}/tokenAuthUsers/{token_auth_user}/authTokens/{auth
   * _token}
   * @param array $optParams Optional parameters.
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
   * Gets a specific auth token for a specific token auth user. (authTokens.get)
   *
   * @param string $name Required. The name of auth token for a token based auth
   * enabled cluster. Format: projects/{project}/locations/{location}/clusters/{cl
   * uster}/tokenAuthUsers/{token_auth_user}/authTokens/{auth_token}
   * @param array $optParams Optional parameters.
   * @return AuthToken
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], AuthToken::class);
  }
  /**
   * Lists all the auth tokens for a specific token auth user.
   * (authTokens.listProjectsLocationsClustersTokenAuthUsersAuthTokens)
   *
   * @param string $parent Required. The parent resource that this auth token will
   * be listed for. Format: projects/{project}/locations/{location}/clusters/{clus
   * ter}/tokenAuthUsers/{token_auth_user}
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Expression for filtering results.
   * @opt_param string orderBy Optional. Sort results by a defined order.
   * @opt_param int pageSize Optional. The maximum number of items to return. The
   * maximum value is 1000; values above 1000 will be coerced to 1000. If not
   * specified, a default value of 1000 will be used by the service. Regardless of
   * the page_size value, the response may include a partial list and a caller
   * should only rely on response's `next_page_token` to determine if there are
   * more clusters left to be queried.
   * @opt_param string pageToken Optional. The `next_page_token` value returned
   * from a previous [ListTokenAuthUsers] request, if any.
   * @return ListAuthTokensResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsClustersTokenAuthUsersAuthTokens($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListAuthTokensResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsClustersTokenAuthUsersAuthTokens::class, 'Google_Service_CloudRedis_Resource_ProjectsLocationsClustersTokenAuthUsersAuthTokens');
