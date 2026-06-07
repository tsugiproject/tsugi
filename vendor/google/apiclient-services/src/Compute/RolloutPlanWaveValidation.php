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

class RolloutPlanWaveValidation extends \Google\Model
{
  protected $timeBasedValidationMetadataType = RolloutPlanWaveValidationTimeBasedValidationMetadata::class;
  protected $timeBasedValidationMetadataDataType = '';
  /**
   * Required. The type of the validation. If a type of validation is associated
   * with a metadata object, the appropriate metadata field mapping to the
   * validation type must be provided in the validation message. Possible values
   * are in quotes below alongside an explanation:   "manual": The system waits
   * for an end-user approval API before     progressing to the next wave.
   * "time": The system waits for a user specified duration before
   * progressing to the next wave. TimeBasedValidation must be provided.
   *
   * @var string
   */
  public $type;

  /**
   * Optional. Metadata required if type = "time".
   *
   * @param RolloutPlanWaveValidationTimeBasedValidationMetadata $timeBasedValidationMetadata
   */
  public function setTimeBasedValidationMetadata(RolloutPlanWaveValidationTimeBasedValidationMetadata $timeBasedValidationMetadata)
  {
    $this->timeBasedValidationMetadata = $timeBasedValidationMetadata;
  }
  /**
   * @return RolloutPlanWaveValidationTimeBasedValidationMetadata
   */
  public function getTimeBasedValidationMetadata()
  {
    return $this->timeBasedValidationMetadata;
  }
  /**
   * Required. The type of the validation. If a type of validation is associated
   * with a metadata object, the appropriate metadata field mapping to the
   * validation type must be provided in the validation message. Possible values
   * are in quotes below alongside an explanation:   "manual": The system waits
   * for an end-user approval API before     progressing to the next wave.
   * "time": The system waits for a user specified duration before
   * progressing to the next wave. TimeBasedValidation must be provided.
   *
   * @param string $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlanWaveValidation::class, 'Google_Service_Compute_RolloutPlanWaveValidation');
