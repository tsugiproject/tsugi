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

use Google\Service\Aiplatform\GoogleApiHttpBody;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1ComputeTokensRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1ComputeTokensResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1CountTokensRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1CountTokensResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1FetchPredictOperationRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1GenerateContentRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1GenerateContentResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1PredictLongRunningRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1PredictRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1PredictResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1RawPredictRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1StreamRawPredictRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1StreamingPredictRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1StreamingPredictResponse;
use Google\Service\Aiplatform\GoogleLongrunningOperation;

/**
 * The "models" collection of methods.
 * Typical usage is:
 *  <code>
 *   $aiplatformService = new Google\Service\Aiplatform(...);
 *   $models = $aiplatformService->projects_locations_publishers_models;
 *  </code>
 */
class ProjectsLocationsPublishersModels extends \Google\Service\Resource
{
  /**
   * Return a list of tokens based on the input text. (models.computeTokens)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to get
   * lists of tokens and token ids.
   * @param GoogleCloudAiplatformV1ComputeTokensRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1ComputeTokensResponse
   * @throws \Google\Service\Exception
   */
  public function computeTokens($endpoint, GoogleCloudAiplatformV1ComputeTokensRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('computeTokens', [$params], GoogleCloudAiplatformV1ComputeTokensResponse::class);
  }
  /**
   * Perform a token counting. (models.countTokens)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to
   * perform token counting. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1CountTokensRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1CountTokensResponse
   * @throws \Google\Service\Exception
   */
  public function countTokens($endpoint, GoogleCloudAiplatformV1CountTokensRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('countTokens', [$params], GoogleCloudAiplatformV1CountTokensResponse::class);
  }
  /**
   * Fetch an asynchronous online prediction operation.
   * (models.fetchPredictOperation)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to serve
   * the prediction. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}` or `projects/{
   * project}/locations/{location}/publishers/{publisher}/models/{model}`
   * @param GoogleCloudAiplatformV1FetchPredictOperationRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function fetchPredictOperation($endpoint, GoogleCloudAiplatformV1FetchPredictOperationRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('fetchPredictOperation', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Generate content with multimodal inputs. (models.generateContent)
   *
   * @param string $model Required. The fully qualified name of the publisher
   * model or tuned model endpoint to use. Publisher model format:
   * `projects/{project}/locations/{location}/publishers/models` Tuned model
   * endpoint format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1GenerateContentRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1GenerateContentResponse
   * @throws \Google\Service\Exception
   */
  public function generateContent($model, GoogleCloudAiplatformV1GenerateContentRequest $postBody, $optParams = [])
  {
    $params = ['model' => $model, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('generateContent', [$params], GoogleCloudAiplatformV1GenerateContentResponse::class);
  }
  /**
   * Perform an online prediction. (models.predict)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to serve
   * the prediction. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1PredictRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1PredictResponse
   * @throws \Google\Service\Exception
   */
  public function predict($endpoint, GoogleCloudAiplatformV1PredictRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('predict', [$params], GoogleCloudAiplatformV1PredictResponse::class);
  }
  /**
   * (models.predictLongRunning)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to serve
   * the prediction. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}` or `projects/{
   * project}/locations/{location}/publishers/{publisher}/models/{model}`
   * @param GoogleCloudAiplatformV1PredictLongRunningRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function predictLongRunning($endpoint, GoogleCloudAiplatformV1PredictLongRunningRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('predictLongRunning', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Perform an online prediction with an arbitrary HTTP payload. The response
   * includes the following HTTP headers: * `X-Vertex-AI-Endpoint-Id`: ID of the
   * Endpoint that served this prediction. * `X-Vertex-AI-Deployed-Model-Id`: ID
   * of the Endpoint's DeployedModel that served this prediction.
   * (models.rawPredict)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to serve
   * the prediction. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1RawPredictRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleApiHttpBody
   * @throws \Google\Service\Exception
   */
  public function rawPredict($endpoint, GoogleCloudAiplatformV1RawPredictRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('rawPredict', [$params], GoogleApiHttpBody::class);
  }
  /**
   * Perform a server-side streaming online prediction request for Vertex LLM
   * streaming. (models.serverStreamingPredict)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to serve
   * the prediction. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1StreamingPredictRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1StreamingPredictResponse
   * @throws \Google\Service\Exception
   */
  public function serverStreamingPredict($endpoint, GoogleCloudAiplatformV1StreamingPredictRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('serverStreamingPredict', [$params], GoogleCloudAiplatformV1StreamingPredictResponse::class);
  }
  /**
   * Generate content with multimodal inputs with streaming support.
   * (models.streamGenerateContent)
   *
   * @param string $model Required. The fully qualified name of the publisher
   * model or tuned model endpoint to use. Publisher model format:
   * `projects/{project}/locations/{location}/publishers/models` Tuned model
   * endpoint format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1GenerateContentRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1GenerateContentResponse
   * @throws \Google\Service\Exception
   */
  public function streamGenerateContent($model, GoogleCloudAiplatformV1GenerateContentRequest $postBody, $optParams = [])
  {
    $params = ['model' => $model, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('streamGenerateContent', [$params], GoogleCloudAiplatformV1GenerateContentResponse::class);
  }
  /**
   * Perform a streaming online prediction with an arbitrary HTTP payload.
   * (models.streamRawPredict)
   *
   * @param string $endpoint Required. The name of the Endpoint requested to serve
   * the prediction. Format:
   * `projects/{project}/locations/{location}/endpoints/{endpoint}`
   * @param GoogleCloudAiplatformV1StreamRawPredictRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleApiHttpBody
   * @throws \Google\Service\Exception
   */
  public function streamRawPredict($endpoint, GoogleCloudAiplatformV1StreamRawPredictRequest $postBody, $optParams = [])
  {
    $params = ['endpoint' => $endpoint, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('streamRawPredict', [$params], GoogleApiHttpBody::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsPublishersModels::class, 'Google_Service_Aiplatform_Resource_ProjectsLocationsPublishersModels');
