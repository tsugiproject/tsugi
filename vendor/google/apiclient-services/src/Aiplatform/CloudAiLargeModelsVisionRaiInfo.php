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

class CloudAiLargeModelsVisionRaiInfo extends \Google\Collection
{
  protected $collection_key = 'scores';
  /**
   * @var string[]
   */
  public $blockedEntities;
  protected $detectedLabelsType = CloudAiLargeModelsVisionRaiInfoDetectedLabels::class;
  protected $detectedLabelsDataType = 'array';
  /**
   * @var string
   */
  public $modelName;
  /**
   * @var string[]
   */
  public $raiCategories;
  /**
   * @var float[]
   */
  public $scores;

  /**
   * @param string[]
   */
  public function setBlockedEntities($blockedEntities)
  {
    $this->blockedEntities = $blockedEntities;
  }
  /**
   * @return string[]
   */
  public function getBlockedEntities()
  {
    return $this->blockedEntities;
  }
  /**
   * @param CloudAiLargeModelsVisionRaiInfoDetectedLabels[]
   */
  public function setDetectedLabels($detectedLabels)
  {
    $this->detectedLabels = $detectedLabels;
  }
  /**
   * @return CloudAiLargeModelsVisionRaiInfoDetectedLabels[]
   */
  public function getDetectedLabels()
  {
    return $this->detectedLabels;
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
   * @param string[]
   */
  public function setRaiCategories($raiCategories)
  {
    $this->raiCategories = $raiCategories;
  }
  /**
   * @return string[]
   */
  public function getRaiCategories()
  {
    return $this->raiCategories;
  }
  /**
   * @param float[]
   */
  public function setScores($scores)
  {
    $this->scores = $scores;
  }
  /**
   * @return float[]
   */
  public function getScores()
  {
    return $this->scores;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CloudAiLargeModelsVisionRaiInfo::class, 'Google_Service_Aiplatform_CloudAiLargeModelsVisionRaiInfo');
