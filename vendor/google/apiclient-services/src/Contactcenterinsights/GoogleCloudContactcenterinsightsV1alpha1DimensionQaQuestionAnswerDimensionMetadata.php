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

class GoogleCloudContactcenterinsightsV1alpha1DimensionQaQuestionAnswerDimensionMetadata extends \Google\Model
{
  /**
   * @var string
   */
  public $answerValue;
  /**
   * @var string
   */
  public $qaQuestionId;
  /**
   * @var string
   */
  public $qaScorecardId;
  /**
   * @var string
   */
  public $questionBody;

  /**
   * @param string
   */
  public function setAnswerValue($answerValue)
  {
    $this->answerValue = $answerValue;
  }
  /**
   * @return string
   */
  public function getAnswerValue()
  {
    return $this->answerValue;
  }
  /**
   * @param string
   */
  public function setQaQuestionId($qaQuestionId)
  {
    $this->qaQuestionId = $qaQuestionId;
  }
  /**
   * @return string
   */
  public function getQaQuestionId()
  {
    return $this->qaQuestionId;
  }
  /**
   * @param string
   */
  public function setQaScorecardId($qaScorecardId)
  {
    $this->qaScorecardId = $qaScorecardId;
  }
  /**
   * @return string
   */
  public function getQaScorecardId()
  {
    return $this->qaScorecardId;
  }
  /**
   * @param string
   */
  public function setQuestionBody($questionBody)
  {
    $this->questionBody = $questionBody;
  }
  /**
   * @return string
   */
  public function getQuestionBody()
  {
    return $this->questionBody;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1alpha1DimensionQaQuestionAnswerDimensionMetadata::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1alpha1DimensionQaQuestionAnswerDimensionMetadata');
