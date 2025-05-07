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

namespace Google\Service\CloudRun;

class GoogleCloudRunV2ServiceScaling extends \Google\Model
{
  /**
   * @var int
   */
  public $manualInstanceCount;
  /**
   * @var int
   */
  public $maxInstanceCount;
  /**
   * @var int
   */
  public $minInstanceCount;
  /**
   * @var string
   */
  public $scalingMode;

  /**
   * @param int
   */
  public function setManualInstanceCount($manualInstanceCount)
  {
    $this->manualInstanceCount = $manualInstanceCount;
  }
  /**
   * @return int
   */
  public function getManualInstanceCount()
  {
    return $this->manualInstanceCount;
  }
  /**
   * @param int
   */
  public function setMaxInstanceCount($maxInstanceCount)
  {
    $this->maxInstanceCount = $maxInstanceCount;
  }
  /**
   * @return int
   */
  public function getMaxInstanceCount()
  {
    return $this->maxInstanceCount;
  }
  /**
   * @param int
   */
  public function setMinInstanceCount($minInstanceCount)
  {
    $this->minInstanceCount = $minInstanceCount;
  }
  /**
   * @return int
   */
  public function getMinInstanceCount()
  {
    return $this->minInstanceCount;
  }
  /**
   * @param string
   */
  public function setScalingMode($scalingMode)
  {
    $this->scalingMode = $scalingMode;
  }
  /**
   * @return string
   */
  public function getScalingMode()
  {
    return $this->scalingMode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRunV2ServiceScaling::class, 'Google_Service_CloudRun_GoogleCloudRunV2ServiceScaling');
