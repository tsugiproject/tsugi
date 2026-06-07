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

class GoogleCloudAiplatformV1EvaluateDatasetRun extends \Google\Model
{
  /**
   * Output only. The checkpoint id used in the evaluation run. Only populated
   * when evaluating checkpoints.
   *
   * @var string
   */
  public $checkpointId;
  protected $errorType = GoogleRpcStatus::class;
  protected $errorDataType = '';
  protected $evaluateDatasetResponseType = GoogleCloudAiplatformV1EvaluateDatasetResponse::class;
  protected $evaluateDatasetResponseDataType = '';
  /**
   * Output only. The resource name of the evaluation run. Format: `projects/{pr
   * oject}/locations/{location}/evaluationRuns/{evaluation_run_id}`.
   *
   * @var string
   */
  public $evaluationRun;
  /**
   * Output only. Deprecated: The updated architecture uses evaluation_run
   * instead.
   *
   * @deprecated
   * @var string
   */
  public $operationName;

  /**
   * Output only. The checkpoint id used in the evaluation run. Only populated
   * when evaluating checkpoints.
   *
   * @param string $checkpointId
   */
  public function setCheckpointId($checkpointId)
  {
    $this->checkpointId = $checkpointId;
  }
  /**
   * @return string
   */
  public function getCheckpointId()
  {
    return $this->checkpointId;
  }
  /**
   * Output only. The error of the evaluation run if any.
   *
   * @param GoogleRpcStatus $error
   */
  public function setError(GoogleRpcStatus $error)
  {
    $this->error = $error;
  }
  /**
   * @return GoogleRpcStatus
   */
  public function getError()
  {
    return $this->error;
  }
  /**
   * Output only. Results for EvaluationService.
   *
   * @param GoogleCloudAiplatformV1EvaluateDatasetResponse $evaluateDatasetResponse
   */
  public function setEvaluateDatasetResponse(GoogleCloudAiplatformV1EvaluateDatasetResponse $evaluateDatasetResponse)
  {
    $this->evaluateDatasetResponse = $evaluateDatasetResponse;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluateDatasetResponse
   */
  public function getEvaluateDatasetResponse()
  {
    return $this->evaluateDatasetResponse;
  }
  /**
   * Output only. The resource name of the evaluation run. Format: `projects/{pr
   * oject}/locations/{location}/evaluationRuns/{evaluation_run_id}`.
   *
   * @param string $evaluationRun
   */
  public function setEvaluationRun($evaluationRun)
  {
    $this->evaluationRun = $evaluationRun;
  }
  /**
   * @return string
   */
  public function getEvaluationRun()
  {
    return $this->evaluationRun;
  }
  /**
   * Output only. Deprecated: The updated architecture uses evaluation_run
   * instead.
   *
   * @deprecated
   * @param string $operationName
   */
  public function setOperationName($operationName)
  {
    $this->operationName = $operationName;
  }
  /**
   * @deprecated
   * @return string
   */
  public function getOperationName()
  {
    return $this->operationName;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1EvaluateDatasetRun::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1EvaluateDatasetRun');
