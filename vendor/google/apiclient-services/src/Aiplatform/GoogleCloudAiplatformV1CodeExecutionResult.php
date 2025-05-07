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

class GoogleCloudAiplatformV1CodeExecutionResult extends \Google\Model
{
  /**
   * @var string
   */
  public $outcome;
  /**
   * @var string
   */
  public $output;

  /**
   * @param string
   */
  public function setOutcome($outcome)
  {
    $this->outcome = $outcome;
  }
  /**
   * @return string
   */
  public function getOutcome()
  {
    return $this->outcome;
  }
  /**
   * @param string
   */
  public function setOutput($output)
  {
    $this->output = $output;
  }
  /**
   * @return string
   */
  public function getOutput()
  {
    return $this->output;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1CodeExecutionResult::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1CodeExecutionResult');
