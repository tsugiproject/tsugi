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

namespace Google\Service\Aiplatform\Resource;

use Google\Service\Aiplatform\GoogleCloudAiplatformV1AugmentPromptRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1AugmentPromptResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1CorroborateContentRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1CorroborateContentResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1EvaluateDatasetRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1EvaluateInstancesRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1EvaluateInstancesResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1RetrieveContextsRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1RetrieveContextsResponse;
use Google\Service\Aiplatform\GoogleCloudLocationListLocationsResponse;
use Google\Service\Aiplatform\GoogleCloudLocationLocation;
use Google\Service\Aiplatform\GoogleLongrunningOperation;

/**
 * The "locations" collection of methods.
 * Typical usage is:
 *  <code>
 *   $aiplatformService = new Google\Service\Aiplatform(...);
 *   $locations = $aiplatformService->projects_locations;
 *  </code>
 */
class ProjectsLocations extends \Google\Service\Resource
{
  /**
   * Given an input prompt, it returns augmented prompt from vertex rag store to
   * guide LLM towards generating grounded responses. (locations.augmentPrompt)
   *
   * @param string $parent Required. The resource name of the Location from which
   * to augment prompt. The users must have permission to make a call in the
   * project. Format: `projects/{project}/locations/{location}`.
   * @param GoogleCloudAiplatformV1AugmentPromptRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1AugmentPromptResponse
   * @throws \Google\Service\Exception
   */
  public function augmentPrompt($parent, GoogleCloudAiplatformV1AugmentPromptRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('augmentPrompt', [$params], GoogleCloudAiplatformV1AugmentPromptResponse::class);
  }
  /**
   * Given an input text, it returns a score that evaluates the factuality of the
   * text. It also extracts and returns claims from the text and provides
   * supporting facts. (locations.corroborateContent)
   *
   * @param string $parent Required. The resource name of the Location from which
   * to corroborate text. The users must have permission to make a call in the
   * project. Format: `projects/{project}/locations/{location}`.
   * @param GoogleCloudAiplatformV1CorroborateContentRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1CorroborateContentResponse
   * @throws \Google\Service\Exception
   */
  public function corroborateContent($parent, GoogleCloudAiplatformV1CorroborateContentRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('corroborateContent', [$params], GoogleCloudAiplatformV1CorroborateContentResponse::class);
  }
  /**
   * Evaluates a dataset based on a set of given metrics.
   * (locations.evaluateDataset)
   *
   * @param string $location Required. The resource name of the Location to
   * evaluate the dataset. Format: `projects/{project}/locations/{location}`
   * @param GoogleCloudAiplatformV1EvaluateDatasetRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function evaluateDataset($location, GoogleCloudAiplatformV1EvaluateDatasetRequest $postBody, $optParams = [])
  {
    $params = ['location' => $location, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('evaluateDataset', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Evaluates instances based on a given metric. (locations.evaluateInstances)
   *
   * @param string $location Required. The resource name of the Location to
   * evaluate the instances. Format: `projects/{project}/locations/{location}`
   * @param GoogleCloudAiplatformV1EvaluateInstancesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1EvaluateInstancesResponse
   * @throws \Google\Service\Exception
   */
  public function evaluateInstances($location, GoogleCloudAiplatformV1EvaluateInstancesRequest $postBody, $optParams = [])
  {
    $params = ['location' => $location, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('evaluateInstances', [$params], GoogleCloudAiplatformV1EvaluateInstancesResponse::class);
  }
  /**
   * Gets information about a location. (locations.get)
   *
   * @param string $name Resource name for the location.
   * @param array $optParams Optional parameters.
   * @return GoogleCloudLocationLocation
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], GoogleCloudLocationLocation::class);
  }
  /**
   * Lists information about the supported locations for this service.
   * (locations.listProjectsLocations)
   *
   * @param string $name The resource that owns the locations collection, if
   * applicable.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter A filter to narrow down results to a preferred
   * subset. The filtering language accepts strings like `"displayName=tokyo"`,
   * and is documented in more detail in [AIP-160](https://google.aip.dev/160).
   * @opt_param int pageSize The maximum number of results to return. If not set,
   * the service selects a default.
   * @opt_param string pageToken A page token received from the `next_page_token`
   * field in the response. Send that page token to receive the subsequent page.
   * @return GoogleCloudLocationListLocationsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocations($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], GoogleCloudLocationListLocationsResponse::class);
  }
  /**
   * Retrieves relevant contexts for a query. (locations.retrieveContexts)
   *
   * @param string $parent Required. The resource name of the Location from which
   * to retrieve RagContexts. The users must have permission to make a call in the
   * project. Format: `projects/{project}/locations/{location}`.
   * @param GoogleCloudAiplatformV1RetrieveContextsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1RetrieveContextsResponse
   * @throws \Google\Service\Exception
   */
  public function retrieveContexts($parent, GoogleCloudAiplatformV1RetrieveContextsRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('retrieveContexts', [$params], GoogleCloudAiplatformV1RetrieveContextsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocations::class, 'Google_Service_Aiplatform_Resource_ProjectsLocations');
