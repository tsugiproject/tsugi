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

namespace Google\Service\SecurityCommandCenter;

class DiscoveredWorkload extends \Google\Model
{
  /**
   * Unspecified confidence level.
   */
  public const CONFIDENCE_CONFIDENCE_UNSPECIFIED = 'CONFIDENCE_UNSPECIFIED';
  /**
   * High confidence in detection of a workload.
   */
  public const CONFIDENCE_CONFIDENCE_HIGH = 'CONFIDENCE_HIGH';
  /**
   * Unspecified workload type
   */
  public const WORKLOAD_TYPE_WORKLOAD_TYPE_UNSPECIFIED = 'WORKLOAD_TYPE_UNSPECIFIED';
  /**
   * A workload of type MCP Server
   */
  public const WORKLOAD_TYPE_MCP_SERVER = 'MCP_SERVER';
  /**
   * A workload of type AI Inference
   */
  public const WORKLOAD_TYPE_AI_INFERENCE = 'AI_INFERENCE';
  /**
   * A workload of type LLM Agent
   */
  public const WORKLOAD_TYPE_AGENT = 'AGENT';
  /**
   * The confidence in detection of this workload.
   *
   * @var string
   */
  public $confidence;
  /**
   * A boolean flag set to true if associated hardware strongly predicts the
   * workload type.
   *
   * @var bool
   */
  public $detectedRelevantHardware;
  /**
   * A boolean flag set to true if associated keywords strongly predict the
   * workload type.
   *
   * @var bool
   */
  public $detectedRelevantKeywords;
  /**
   * A boolean flag set to true if installed packages strongly predict the
   * workload type.
   *
   * @var bool
   */
  public $detectedRelevantPackages;
  /**
   * The type of workload.
   *
   * @var string
   */
  public $workloadType;

  /**
   * The confidence in detection of this workload.
   *
   * Accepted values: CONFIDENCE_UNSPECIFIED, CONFIDENCE_HIGH
   *
   * @param self::CONFIDENCE_* $confidence
   */
  public function setConfidence($confidence)
  {
    $this->confidence = $confidence;
  }
  /**
   * @return self::CONFIDENCE_*
   */
  public function getConfidence()
  {
    return $this->confidence;
  }
  /**
   * A boolean flag set to true if associated hardware strongly predicts the
   * workload type.
   *
   * @param bool $detectedRelevantHardware
   */
  public function setDetectedRelevantHardware($detectedRelevantHardware)
  {
    $this->detectedRelevantHardware = $detectedRelevantHardware;
  }
  /**
   * @return bool
   */
  public function getDetectedRelevantHardware()
  {
    return $this->detectedRelevantHardware;
  }
  /**
   * A boolean flag set to true if associated keywords strongly predict the
   * workload type.
   *
   * @param bool $detectedRelevantKeywords
   */
  public function setDetectedRelevantKeywords($detectedRelevantKeywords)
  {
    $this->detectedRelevantKeywords = $detectedRelevantKeywords;
  }
  /**
   * @return bool
   */
  public function getDetectedRelevantKeywords()
  {
    return $this->detectedRelevantKeywords;
  }
  /**
   * A boolean flag set to true if installed packages strongly predict the
   * workload type.
   *
   * @param bool $detectedRelevantPackages
   */
  public function setDetectedRelevantPackages($detectedRelevantPackages)
  {
    $this->detectedRelevantPackages = $detectedRelevantPackages;
  }
  /**
   * @return bool
   */
  public function getDetectedRelevantPackages()
  {
    return $this->detectedRelevantPackages;
  }
  /**
   * The type of workload.
   *
   * Accepted values: WORKLOAD_TYPE_UNSPECIFIED, MCP_SERVER, AI_INFERENCE, AGENT
   *
   * @param self::WORKLOAD_TYPE_* $workloadType
   */
  public function setWorkloadType($workloadType)
  {
    $this->workloadType = $workloadType;
  }
  /**
   * @return self::WORKLOAD_TYPE_*
   */
  public function getWorkloadType()
  {
    return $this->workloadType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DiscoveredWorkload::class, 'Google_Service_SecurityCommandCenter_DiscoveredWorkload');
