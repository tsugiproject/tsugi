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

namespace Google\Service\Compute;

class RolloutPlanWave extends \Google\Collection
{
  protected $collection_key = 'selectors';
  /**
   * Optional. The display name of this wave of the rollout plan.
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. The wave number.
   *
   * @var string
   */
  public $number;
  protected $orchestrationOptionsType = RolloutPlanWaveOrchestrationOptions::class;
  protected $orchestrationOptionsDataType = '';
  protected $selectorsType = RolloutPlanWaveSelector::class;
  protected $selectorsDataType = 'array';
  protected $validationType = RolloutPlanWaveValidation::class;
  protected $validationDataType = '';

  /**
   * Optional. The display name of this wave of the rollout plan.
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Output only. The wave number.
   *
   * @param string $number
   */
  public function setNumber($number)
  {
    $this->number = $number;
  }
  /**
   * @return string
   */
  public function getNumber()
  {
    return $this->number;
  }
  /**
   * Optional. The orchestration options for this wave.
   *
   * @param RolloutPlanWaveOrchestrationOptions $orchestrationOptions
   */
  public function setOrchestrationOptions(RolloutPlanWaveOrchestrationOptions $orchestrationOptions)
  {
    $this->orchestrationOptions = $orchestrationOptions;
  }
  /**
   * @return RolloutPlanWaveOrchestrationOptions
   */
  public function getOrchestrationOptions()
  {
    return $this->orchestrationOptions;
  }
  /**
   * Required. The selectors for this wave. There is a logical AND between each
   * selector defined in a wave, so a resource must satisfy the criteria of
   * *all* the specified selectors to be in scope for the wave.
   *
   * @param RolloutPlanWaveSelector[] $selectors
   */
  public function setSelectors($selectors)
  {
    $this->selectors = $selectors;
  }
  /**
   * @return RolloutPlanWaveSelector[]
   */
  public function getSelectors()
  {
    return $this->selectors;
  }
  /**
   * Required. The validation to be performed at the end of this wave.
   *
   * @param RolloutPlanWaveValidation $validation
   */
  public function setValidation(RolloutPlanWaveValidation $validation)
  {
    $this->validation = $validation;
  }
  /**
   * @return RolloutPlanWaveValidation
   */
  public function getValidation()
  {
    return $this->validation;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlanWave::class, 'Google_Service_Compute_RolloutPlanWave');
