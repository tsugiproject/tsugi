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

namespace Google\Service\Appengine\Resource;

use Google\Service\Appengine\ListServicesResponse;
use Google\Service\Appengine\Operation;
use Google\Service\Appengine\Service;

/**
 * The "services" collection of methods.
 * Typical usage is:
 *  <code>
 *   $appengineService = new Google\Service\Appengine(...);
 *   $services = $appengineService->apps_services;
 *  </code>
 */
class AppsServices extends \Google\Service\Resource
{
  /**
   * Deletes the specified service and all enclosed versions. (services.delete)
   *
   * @param string $appsId Part of `name`. Name of the resource requested.
   * Example: apps/myapp/services/default.
   * @param string $servicesId Part of `name`. See documentation of `appsId`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool force Optional. If set to true, any versions of this service
   * will also be deleted. (Otherwise, the request will only succeed if the
   * service has no versions.)
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function delete($appsId, $servicesId, $optParams = [])
  {
    $params = ['appsId' => $appsId, 'servicesId' => $servicesId];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], Operation::class);
  }
  /**
   * Gets the current configuration of the specified service. (services.get)
   *
   * @param string $appsId Part of `name`. Name of the resource requested.
   * Example: apps/myapp/services/default.
   * @param string $servicesId Part of `name`. See documentation of `appsId`.
   * @param array $optParams Optional parameters.
   * @return Service
   * @throws \Google\Service\Exception
   */
  public function get($appsId, $servicesId, $optParams = [])
  {
    $params = ['appsId' => $appsId, 'servicesId' => $servicesId];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Service::class);
  }
  /**
   * Lists all the services in the application. (services.listAppsServices)
   *
   * @param string $appsId Part of `parent`. Name of the parent Application
   * resource. Example: apps/myapp.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Maximum results to return per page.
   * @opt_param string pageToken Continuation token for fetching the next page of
   * results.
   * @return ListServicesResponse
   * @throws \Google\Service\Exception
   */
  public function listAppsServices($appsId, $optParams = [])
  {
    $params = ['appsId' => $appsId];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListServicesResponse::class);
  }
  /**
   * Updates the configuration of the specified service. (services.patch)
   *
   * @param string $appsId Part of `name`. Name of the resource to update.
   * Example: apps/myapp/services/default.
   * @param string $servicesId Part of `name`. See documentation of `appsId`.
   * @param Service $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool migrateTraffic Set to true to gradually shift traffic to one
   * or more versions that you specify. By default, traffic is shifted
   * immediately. For gradual traffic migration, the target versions must be
   * located within instances that are configured for both warmup requests
   * (https://cloud.google.com/appengine/docs/admin-
   * api/reference/rest/v1/apps.services.versions#InboundServiceType) and
   * automatic scaling (https://cloud.google.com/appengine/docs/admin-
   * api/reference/rest/v1/apps.services.versions#AutomaticScaling). You must
   * specify the shardBy (https://cloud.google.com/appengine/docs/admin-
   * api/reference/rest/v1/apps.services#ShardBy) field in the Service resource.
   * Gradual traffic migration is not supported in the App Engine flexible
   * environment. For examples, see Migrating and Splitting Traffic
   * (https://cloud.google.com/appengine/docs/admin-api/migrating-splitting-
   * traffic).
   * @opt_param string updateMask Required. Standard field mask for the set of
   * fields to be updated.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($appsId, $servicesId, Service $postBody, $optParams = [])
  {
    $params = ['appsId' => $appsId, 'servicesId' => $servicesId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AppsServices::class, 'Google_Service_Appengine_Resource_AppsServices');
