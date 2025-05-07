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

namespace Google\Service\Contactcenterinsights;

class GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerSource extends \Google\Model
{
  protected $answerValueType = GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerValue::class;
  protected $answerValueDataType = '';
  /**
   * @var string
   */
  public $sourceType;

  /**
   * @param GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerValue
   */
  public function setAnswerValue(GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerValue $answerValue)
  {
    $this->answerValue = $answerValue;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerValue
   */
  public function getAnswerValue()
  {
    return $this->answerValue;
  }
  /**
   * @param string
   */
  public function setSourceType($sourceType)
  {
    $this->sourceType = $sourceType;
  }
  /**
   * @return string
   */
  public function getSourceType()
  {
    return $this->sourceType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerSource::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1alpha1QaAnswerAnswerSource');
