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

namespace Google\Service\CloudDeploy\Resource;

use Google\Service\CloudDeploy\AdvanceRolloutRequest;
use Google\Service\CloudDeploy\AdvanceRolloutResponse;
use Google\Service\CloudDeploy\ApproveRolloutRequest;
use Google\Service\CloudDeploy\ApproveRolloutResponse;
use Google\Service\CloudDeploy\CancelRolloutRequest;
use Google\Service\CloudDeploy\CancelRolloutResponse;
use Google\Service\CloudDeploy\IgnoreJobRequest;
use Google\Service\CloudDeploy\IgnoreJobResponse;
use Google\Service\CloudDeploy\ListRolloutsResponse;
use Google\Service\CloudDeploy\Operation;
use Google\Service\CloudDeploy\RetryJobRequest;
use Google\Service\CloudDeploy\RetryJobResponse;
use Google\Service\CloudDeploy\Rollout;

/**
 * The "rollouts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $clouddeployService = new Google\Service\CloudDeploy(...);
 *   $rollouts = $clouddeployService->projects_locations_deliveryPipelines_releases_rollouts;
 *  </code>
 */
class ProjectsLocationsDeliveryPipelinesReleasesRollouts extends \Google\Service\Resource
{
  /**
   * Advances a Rollout in a given project and location. (rollouts.advance)
   *
   * @param string $name Required. Name of the Rollout. Format is `projects/{proje
   * ct}/locations/{location}/deliveryPipelines/{deliveryPipeline}/releases/{relea
   * se}/rollouts/{rollout}`.
   * @param AdvanceRolloutRequest $postBody
   * @param array $optParams Optional parameters.
   * @return AdvanceRolloutResponse
   * @throws \Google\Service\Exception
   */
  public function advance($name, AdvanceRolloutRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('advance', [$params], AdvanceRolloutResponse::class);
  }
  /**
   * Approves a Rollout. (rollouts.approve)
   *
   * @param string $name Required. Name of the Rollout. Format is `projects/{proje
   * ct}/locations/{location}/deliveryPipelines/{deliveryPipeline}/releases/{relea
   * se}/rollouts/{rollout}`.
   * @param ApproveRolloutRequest $postBody
   * @param array $optParams Optional parameters.
   * @return ApproveRolloutResponse
   * @throws \Google\Service\Exception
   */
  public function approve($name, ApproveRolloutRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('approve', [$params], ApproveRolloutResponse::class);
  }
  /**
   * Cancels a Rollout in a given project and location. (rollouts.cancel)
   *
   * @param string $name Required. Name of the Rollout. Format is `projects/{proje
   * ct}/locations/{location}/deliveryPipelines/{deliveryPipeline}/releases/{relea
   * se}/rollouts/{rollout}`.
   * @param CancelRolloutRequest $postBody
   * @param array $optParams Optional parameters.
   * @return CancelRolloutResponse
   * @throws \Google\Service\Exception
   */
  public function cancel($name, CancelRolloutRequest $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('cancel', [$params], CancelRolloutResponse::class);
  }
  /**
   * Creates a new Rollout in a given project and location. (rollouts.create)
   *
   * @param string $parent Required. The parent collection in which the `Rollout`
   * must be created. The format is `projects/{project_id}/locations/{location_nam
   * e}/deliveryPipelines/{pipeline_name}/releases/{release_name}`.
   * @param Rollout $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string overrideDeployPolicy Optional. Deploy policies to override.
   * Format is
   * `projects/{project}/locations/{location}/deployPolicies/{deployPolicy}`.
   * @opt_param string requestId Optional. A request ID to identify requests.
   * Specify a unique request ID so that if you must retry your request, the
   * server knows to ignore the request if it has already been completed. The
   * server guarantees that for at least 60 minutes after the first request. For
   * example, consider a situation where you make an initial request and the
   * request times out. If you make the request again with the same request ID,
   * the server can check if original operation with the same request ID was
   * received, and if so, will ignore the second request. This prevents clients
   * from accidentally creating duplicate commitments. The request ID must be a
   * valid UUID with the exception that zero UUID is not supported
   * (00000000-0000-0000-0000-000000000000).
   * @opt_param string rolloutId Required. ID of the `Rollout`.
   * @opt_param string startingPhaseId Optional. The starting phase ID for the
   * `Rollout`. If empty the `Rollout` will start at the first phase.
   * @opt_param bool validateOnly Optional. If set to true, the request is
   * validated and the user is provided with an expected result, but no actual
   * change is made.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, Rollout $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Gets details of a single Rollout. (rollouts.get)
   *
   * @param string $name Required. Name of the `Rollout`. Format must be `projects
   * /{project_id}/locations/{location_name}/deliveryPipelines/{pipeline_name}/rel
   * eases/{release_name}/rollouts/{rollout_name}`.
   * @param array $optParams Optional parameters.
   * @return Rollout
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Rollout::class);
  }
  /**
   * Ignores the specified Job in a Rollout. (rollouts.ignoreJob)
   *
   * @param string $rollout Required. Name of the Rollout. Format is `projects/{pr
   * oject}/locations/{location}/deliveryPipelines/{deliveryPipeline}/releases/{re
   * lease}/rollouts/{rollout}`.
   * @param IgnoreJobRequest $postBody
   * @param array $optParams Optional parameters.
   * @return IgnoreJobResponse
   * @throws \Google\Service\Exception
   */
  public function ignoreJob($rollout, IgnoreJobRequest $postBody, $optParams = [])
  {
    $params = ['rollout' => $rollout, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('ignoreJob', [$params], IgnoreJobResponse::class);
  }
  /**
   * Lists Rollouts in a given project and location.
   * (rollouts.listProjectsLocationsDeliveryPipelinesReleasesRollouts)
   *
   * @param string $parent Required. The `Release` which owns this collection of
   * `Rollout` objects.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. Filter rollouts to be returned. See
   * https://google.aip.dev/160 for more details.
   * @opt_param string orderBy Optional. Field to sort by. See
   * https://google.aip.dev/132#ordering for more details.
   * @opt_param int pageSize Optional. The maximum number of `Rollout` objects to
   * return. The service may return fewer than this value. If unspecified, at most
   * 50 `Rollout` objects will be returned. The maximum value is 1000; values
   * above 1000 will be set to 1000.
   * @opt_param string pageToken Optional. A page token, received from a previous
   * `ListRollouts` call. Provide this to retrieve the subsequent page. When
   * paginating, all other provided parameters match the call that provided the
   * page token.
   * @return ListRolloutsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsDeliveryPipelinesReleasesRollouts($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListRolloutsResponse::class);
  }
  /**
   * Retries the specified Job in a Rollout. (rollouts.retryJob)
   *
   * @param string $rollout Required. Name of the Rollout. Format is `projects/{pr
   * oject}/locations/{location}/deliveryPipelines/{deliveryPipeline}/releases/{re
   * lease}/rollouts/{rollout}`.
   * @param RetryJobRequest $postBody
   * @param array $optParams Optional parameters.
   * @return RetryJobResponse
   * @throws \Google\Service\Exception
   */
  public function retryJob($rollout, RetryJobRequest $postBody, $optParams = [])
  {
    $params = ['rollout' => $rollout, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('retryJob', [$params], RetryJobResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDeliveryPipelinesReleasesRollouts::class, 'Google_Service_CloudDeploy_Resource_ProjectsLocationsDeliveryPipelinesReleasesRollouts');
