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

namespace Google\Service\Spanner\Resource;

use Google\Service\Spanner\CreateInstanceConfigRequest;
use Google\Service\Spanner\InstanceConfig;
use Google\Service\Spanner\ListInstanceConfigsResponse;
use Google\Service\Spanner\Operation;
use Google\Service\Spanner\SpannerEmpty;
use Google\Service\Spanner\UpdateInstanceConfigRequest;

/**
 * The "instanceConfigs" collection of methods.
 * Typical usage is:
 *  <code>
 *   $spannerService = new Google\Service\Spanner(...);
 *   $instanceConfigs = $spannerService->projects_instanceConfigs;
 *  </code>
 */
class ProjectsInstanceConfigs extends \Google\Service\Resource
{
  /**
   * Creates an instance configuration and begins preparing it to be used. The
   * returned long-running operation can be used to track the progress of
   * preparing the new instance configuration. The instance configuration name is
   * assigned by the caller. If the named instance configuration already exists,
   * `CreateInstanceConfig` returns `ALREADY_EXISTS`. Immediately after the
   * request returns: * The instance configuration is readable via the API, with
   * all requested attributes. The instance configuration's reconciling field is
   * set to true. Its state is `CREATING`. While the operation is pending: *
   * Cancelling the operation renders the instance configuration immediately
   * unreadable via the API. * Except for deleting the creating resource, all
   * other attempts to modify the instance configuration are rejected. Upon
   * completion of the returned operation: * Instances can be created using the
   * instance configuration. * The instance configuration's reconciling field
   * becomes false. Its state becomes `READY`. The returned long-running operation
   * will have a name of the format `/operations/` and can be used to track
   * creation of the instance configuration. The metadata field type is
   * CreateInstanceConfigMetadata. The response field type is InstanceConfig, if
   * successful. Authorization requires `spanner.instanceConfigs.create`
   * permission on the resource parent. (instanceConfigs.create)
   *
   * @param string $parent Required. The name of the project in which to create
   * the instance configuration. Values are of the form `projects/`.
   * @param CreateInstanceConfigRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, CreateInstanceConfigRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes the instance configuration. Deletion is only allowed when no
   * instances are using the configuration. If any instances are using the
   * configuration, returns `FAILED_PRECONDITION`. Only user-managed
   * configurations can be deleted. Authorization requires
   * `spanner.instanceConfigs.delete` permission on the resource name.
   * (instanceConfigs.delete)
   *
   * @param string $name Required. The name of the instance configuration to be
   * deleted. Values are of the form `projects//instanceConfigs/`
   * @param array $optParams Optional parameters.
   *
   * @opt_param string etag Used for optimistic concurrency control as a way to
   * help prevent simultaneous deletes of an instance configuration from
   * overwriting each other. If not empty, the API only deletes the instance
   * configuration when the etag provided matches the current status of the
   * requested instance configuration. Otherwise, deletes the instance
   * configuration without checking the current status of the requested instance
   * configuration.
   * @opt_param bool validateOnly An option to validate, but not actually execute,
   * a request, and provide the same response.
   * @return SpannerEmpty
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], SpannerEmpty::class);
  }
  /**
   * Gets information about a particular instance configuration.
   * (instanceConfigs.get)
   *
   * @param string $name Required. The name of the requested instance
   * configuration. Values are of the form `projects//instanceConfigs/`.
   * @param array $optParams Optional parameters.
   * @return InstanceConfig
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], InstanceConfig::class);
  }
  /**
   * Lists the supported instance configurations for a given project. Returns both
   * Google-managed configurations and user-managed configurations.
   * (instanceConfigs.listProjectsInstanceConfigs)
   *
   * @param string $parent Required. The name of the project for which a list of
   * supported instance configurations is requested. Values are of the form
   * `projects/`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Number of instance configurations to be returned in
   * the response. If 0 or less, defaults to the server's maximum allowed page
   * size.
   * @opt_param string pageToken If non-empty, `page_token` should contain a
   * next_page_token from a previous ListInstanceConfigsResponse.
   * @return ListInstanceConfigsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsInstanceConfigs($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListInstanceConfigsResponse::class);
  }
  /**
   * Updates an instance configuration. The returned long-running operation can be
   * used to track the progress of updating the instance. If the named instance
   * configuration does not exist, returns `NOT_FOUND`. Only user-managed
   * configurations can be updated. Immediately after the request returns: * The
   * instance configuration's reconciling field is set to true. While the
   * operation is pending: * Cancelling the operation sets its metadata's
   * cancel_time. The operation is guaranteed to succeed at undoing all changes,
   * after which point it terminates with a `CANCELLED` status. * All other
   * attempts to modify the instance configuration are rejected. * Reading the
   * instance configuration via the API continues to give the pre-request values.
   * Upon completion of the returned operation: * Creating instances using the
   * instance configuration uses the new values. * The new values of the instance
   * configuration are readable via the API. * The instance configuration's
   * reconciling field becomes false. The returned long-running operation will
   * have a name of the format `/operations/` and can be used to track the
   * instance configuration modification. The metadata field type is
   * UpdateInstanceConfigMetadata. The response field type is InstanceConfig, if
   * successful. Authorization requires `spanner.instanceConfigs.update`
   * permission on the resource name. (instanceConfigs.patch)
   *
   * @param string $name A unique identifier for the instance configuration.
   * Values are of the form `projects//instanceConfigs/a-z*`. User instance
   * configuration must start with `custom-`.
   * @param UpdateInstanceConfigRequest $postBody
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function patch($name, UpdateInstanceConfigRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Operation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsInstanceConfigs::class, 'Google_Service_Spanner_Resource_ProjectsInstanceConfigs');
