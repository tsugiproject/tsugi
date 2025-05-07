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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1betaTrainCustomModelResponse extends \Google\Collection
{
  protected $collection_key = 'errorSamples';
  protected $errorConfigType = GoogleCloudDiscoveryengineV1betaImportErrorConfig::class;
  protected $errorConfigDataType = '';
  protected $errorSamplesType = GoogleRpcStatus::class;
  protected $errorSamplesDataType = 'array';
  public $metrics;
  /**
   * @var string
   */
  public $modelName;
  /**
   * @var string
   */
  public $modelStatus;

  /**
   * @param GoogleCloudDiscoveryengineV1betaImportErrorConfig
   */
  public function setErrorConfig(GoogleCloudDiscoveryengineV1betaImportErrorConfig $errorConfig)
  {
    $this->errorConfig = $errorConfig;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1betaImportErrorConfig
   */
  public function getErrorConfig()
  {
    return $this->errorConfig;
  }
  /**
   * @param GoogleRpcStatus[]
   */
  public function setErrorSamples($errorSamples)
  {
    $this->errorSamples = $errorSamples;
  }
  /**
   * @return GoogleRpcStatus[]
   */
  public function getErrorSamples()
  {
    return $this->errorSamples;
  }
  public function setMetrics($metrics)
  {
    $this->metrics = $metrics;
  }
  public function getMetrics()
  {
    return $this->metrics;
  }
  /**
   * @param string
   */
  public function setModelName($modelName)
  {
    $this->modelName = $modelName;
  }
  /**
   * @return string
   */
  public function getModelName()
  {
    return $this->modelName;
  }
  /**
   * @param string
   */
  public function setModelStatus($modelStatus)
  {
    $this->modelStatus = $modelStatus;
  }
  /**
   * @return string
   */
  public function getModelStatus()
  {
    return $this->modelStatus;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1betaTrainCustomModelResponse::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1betaTrainCustomModelResponse');
