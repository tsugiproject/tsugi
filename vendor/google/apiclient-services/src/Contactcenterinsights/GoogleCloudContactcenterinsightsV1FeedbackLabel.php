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

class GoogleCloudContactcenterinsightsV1FeedbackLabel extends \Google\Model
{
  /**
   * @var string
   */
  public $createTime;
  /**
   * @var string
   */
  public $label;
  /**
   * @var string
   */
  public $labeledResource;
  /**
   * @var string
   */
  public $name;
  protected $qaAnswerLabelType = GoogleCloudContactcenterinsightsV1QaAnswerAnswerValue::class;
  protected $qaAnswerLabelDataType = '';
  /**
   * @var string
   */
  public $updateTime;

  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param string
   */
  public function setLabel($label)
  {
    $this->label = $label;
  }
  /**
   * @return string
   */
  public function getLabel()
  {
    return $this->label;
  }
  /**
   * @param string
   */
  public function setLabeledResource($labeledResource)
  {
    $this->labeledResource = $labeledResource;
  }
  /**
   * @return string
   */
  public function getLabeledResource()
  {
    return $this->labeledResource;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param GoogleCloudContactcenterinsightsV1QaAnswerAnswerValue
   */
  public function setQaAnswerLabel(GoogleCloudContactcenterinsightsV1QaAnswerAnswerValue $qaAnswerLabel)
  {
    $this->qaAnswerLabel = $qaAnswerLabel;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1QaAnswerAnswerValue
   */
  public function getQaAnswerLabel()
  {
    return $this->qaAnswerLabel;
  }
  /**
   * @param string
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1FeedbackLabel::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1FeedbackLabel');
