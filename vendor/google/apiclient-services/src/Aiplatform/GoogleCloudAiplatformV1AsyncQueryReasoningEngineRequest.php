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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1AsyncQueryReasoningEngineRequest extends \Google\Model
{
  /**
   * Optional. Input Cloud Storage URI for the Async query. If you are not
   * bringing your own container (BYOC), the content of the file should be a
   * JSON object with an `input` field matching the `input` field of
   * `QueryReasoningEngineRequest` (e.g. `{ "input": { "user_id": "hello",
   * "message":"$QUERY"} }`). For BYOC, the content of the file depends on the
   * the agent application.
   *
   * @var string
   */
  public $inputGcsUri;
  /**
   * Optional. Output Cloud Storage URI for the Async query. This contains the
   * final response of the query.
   *
   * @var string
   */
  public $outputGcsUri;

  /**
   * Optional. Input Cloud Storage URI for the Async query. If you are not
   * bringing your own container (BYOC), the content of the file should be a
   * JSON object with an `input` field matching the `input` field of
   * `QueryReasoningEngineRequest` (e.g. `{ "input": { "user_id": "hello",
   * "message":"$QUERY"} }`). For BYOC, the content of the file depends on the
   * the agent application.
   *
   * @param string $inputGcsUri
   */
  public function setInputGcsUri($inputGcsUri)
  {
    $this->inputGcsUri = $inputGcsUri;
  }
  /**
   * @return string
   */
  public function getInputGcsUri()
  {
    return $this->inputGcsUri;
  }
  /**
   * Optional. Output Cloud Storage URI for the Async query. This contains the
   * final response of the query.
   *
   * @param string $outputGcsUri
   */
  public function setOutputGcsUri($outputGcsUri)
  {
    $this->outputGcsUri = $outputGcsUri;
  }
  /**
   * @return string
   */
  public function getOutputGcsUri()
  {
    return $this->outputGcsUri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1AsyncQueryReasoningEngineRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1AsyncQueryReasoningEngineRequest');
