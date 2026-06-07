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

class GoogleDevtoolsCloudbuildV1StepResult extends \Google\Model
{
  /**
   * Optional. The content of the attestation to be generated.
   *
   * @var string
   */
  public $attestationContent;
  /**
   * Optional. The type of attestation to be generated.
   *
   * @var string
   */
  public $attestationType;
  /**
   * Required. The name of the result.
   *
   * @var string
   */
  public $name;

  /**
   * Optional. The content of the attestation to be generated.
   *
   * @param string $attestationContent
   */
  public function setAttestationContent($attestationContent)
  {
    $this->attestationContent = $attestationContent;
  }
  /**
   * @return string
   */
  public function getAttestationContent()
  {
    return $this->attestationContent;
  }
  /**
   * Optional. The type of attestation to be generated.
   *
   * @param string $attestationType
   */
  public function setAttestationType($attestationType)
  {
    $this->attestationType = $attestationType;
  }
  /**
   * @return string
   */
  public function getAttestationType()
  {
    return $this->attestationType;
  }
  /**
   * Required. The name of the result.
   *
   * @param string $name
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleDevtoolsCloudbuildV1StepResult::class, 'Google_Service_CloudRun_GoogleDevtoolsCloudbuildV1StepResult');
