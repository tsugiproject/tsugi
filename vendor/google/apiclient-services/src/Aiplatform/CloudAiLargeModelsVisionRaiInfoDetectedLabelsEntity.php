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

class CloudAiLargeModelsVisionRaiInfoDetectedLabelsEntity extends \Google\Model
{
  protected $boundingBoxType = CloudAiLargeModelsVisionRaiInfoDetectedLabelsBoundingBox::class;
  protected $boundingBoxDataType = '';
  /**
   * @var string
   */
  public $description;
  /**
   * @var float
   */
  public $iouScore;
  /**
   * @var string
   */
  public $mid;
  /**
   * @var float
   */
  public $score;

  /**
   * @param CloudAiLargeModelsVisionRaiInfoDetectedLabelsBoundingBox
   */
  public function setBoundingBox(CloudAiLargeModelsVisionRaiInfoDetectedLabelsBoundingBox $boundingBox)
  {
    $this->boundingBox = $boundingBox;
  }
  /**
   * @return CloudAiLargeModelsVisionRaiInfoDetectedLabelsBoundingBox
   */
  public function getBoundingBox()
  {
    return $this->boundingBox;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param float
   */
  public function setIouScore($iouScore)
  {
    $this->iouScore = $iouScore;
  }
  /**
   * @return float
   */
  public function getIouScore()
  {
    return $this->iouScore;
  }
  /**
   * @param string
   */
  public function setMid($mid)
  {
    $this->mid = $mid;
  }
  /**
   * @return string
   */
  public function getMid()
  {
    return $this->mid;
  }
  /**
   * @param float
   */
  public function setScore($score)
  {
    $this->score = $score;
  }
  /**
   * @return float
   */
  public function getScore()
  {
    return $this->score;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CloudAiLargeModelsVisionRaiInfoDetectedLabelsEntity::class, 'Google_Service_Aiplatform_CloudAiLargeModelsVisionRaiInfoDetectedLabelsEntity');
