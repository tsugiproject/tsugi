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

class GoogleCloudAiplatformV1QueryReasoningEngineRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $classMethod;
  /**
   * @var array[]
   */
  public $input;

  /**
   * @param string
   */
  public function setClassMethod($classMethod)
  {
    $this->classMethod = $classMethod;
  }
  /**
   * @return string
   */
  public function getClassMethod()
  {
    return $this->classMethod;
  }
  /**
   * @param array[]
   */
  public function setInput($input)
  {
    $this->input = $input;
  }
  /**
   * @return array[]
   */
  public function getInput()
  {
    return $this->input;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1QueryReasoningEngineRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1QueryReasoningEngineRequest');
