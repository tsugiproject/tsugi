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

class GoogleCloudAiplatformV1QuestionAnsweringCorrectnessInstance extends \Google\Model
{
  /**
   * @var string
   */
  public $context;
  /**
   * @var string
   */
  public $instruction;
  /**
   * @var string
   */
  public $prediction;
  /**
   * @var string
   */
  public $reference;

  /**
   * @param string
   */
  public function setContext($context)
  {
    $this->context = $context;
  }
  /**
   * @return string
   */
  public function getContext()
  {
    return $this->context;
  }
  /**
   * @param string
   */
  public function setInstruction($instruction)
  {
    $this->instruction = $instruction;
  }
  /**
   * @return string
   */
  public function getInstruction()
  {
    return $this->instruction;
  }
  /**
   * @param string
   */
  public function setPrediction($prediction)
  {
    $this->prediction = $prediction;
  }
  /**
   * @return string
   */
  public function getPrediction()
  {
    return $this->prediction;
  }
  /**
   * @param string
   */
  public function setReference($reference)
  {
    $this->reference = $reference;
  }
  /**
   * @return string
   */
  public function getReference()
  {
    return $this->reference;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1QuestionAnsweringCorrectnessInstance::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1QuestionAnsweringCorrectnessInstance');
