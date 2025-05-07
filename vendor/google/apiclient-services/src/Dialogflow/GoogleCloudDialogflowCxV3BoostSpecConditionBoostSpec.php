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

namespace Google\Service\Dialogflow;

class GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpec extends \Google\Model
{
  /**
   * @var float
   */
  public $boost;
  protected $boostControlSpecType = GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpecBoostControlSpec::class;
  protected $boostControlSpecDataType = '';
  /**
   * @var string
   */
  public $condition;

  /**
   * @param float
   */
  public function setBoost($boost)
  {
    $this->boost = $boost;
  }
  /**
   * @return float
   */
  public function getBoost()
  {
    return $this->boost;
  }
  /**
   * @param GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpecBoostControlSpec
   */
  public function setBoostControlSpec(GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpecBoostControlSpec $boostControlSpec)
  {
    $this->boostControlSpec = $boostControlSpec;
  }
  /**
   * @return GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpecBoostControlSpec
   */
  public function getBoostControlSpec()
  {
    return $this->boostControlSpec;
  }
  /**
   * @param string
   */
  public function setCondition($condition)
  {
    $this->condition = $condition;
  }
  /**
   * @return string
   */
  public function getCondition()
  {
    return $this->condition;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpec::class, 'Google_Service_Dialogflow_GoogleCloudDialogflowCxV3BoostSpecConditionBoostSpec');
