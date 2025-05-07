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

namespace Google\Service\Compute;

class ConfidentialInstanceConfig extends \Google\Model
{
  /**
   * @var string
   */
  public $confidentialInstanceType;
  /**
   * @var bool
   */
  public $enableConfidentialCompute;

  /**
   * @param string
   */
  public function setConfidentialInstanceType($confidentialInstanceType)
  {
    $this->confidentialInstanceType = $confidentialInstanceType;
  }
  /**
   * @return string
   */
  public function getConfidentialInstanceType()
  {
    return $this->confidentialInstanceType;
  }
  /**
   * @param bool
   */
  public function setEnableConfidentialCompute($enableConfidentialCompute)
  {
    $this->enableConfidentialCompute = $enableConfidentialCompute;
  }
  /**
   * @return bool
   */
  public function getEnableConfidentialCompute()
  {
    return $this->enableConfidentialCompute;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ConfidentialInstanceConfig::class, 'Google_Service_Compute_ConfidentialInstanceConfig');
