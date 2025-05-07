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

class CustomErrorResponsePolicyCustomErrorResponseRule extends \Google\Collection
{
  protected $collection_key = 'matchResponseCodes';
  /**
   * @var string[]
   */
  public $matchResponseCodes;
  /**
   * @var int
   */
  public $overrideResponseCode;
  /**
   * @var string
   */
  public $path;

  /**
   * @param string[]
   */
  public function setMatchResponseCodes($matchResponseCodes)
  {
    $this->matchResponseCodes = $matchResponseCodes;
  }
  /**
   * @return string[]
   */
  public function getMatchResponseCodes()
  {
    return $this->matchResponseCodes;
  }
  /**
   * @param int
   */
  public function setOverrideResponseCode($overrideResponseCode)
  {
    $this->overrideResponseCode = $overrideResponseCode;
  }
  /**
   * @return int
   */
  public function getOverrideResponseCode()
  {
    return $this->overrideResponseCode;
  }
  /**
   * @param string
   */
  public function setPath($path)
  {
    $this->path = $path;
  }
  /**
   * @return string
   */
  public function getPath()
  {
    return $this->path;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CustomErrorResponsePolicyCustomErrorResponseRule::class, 'Google_Service_Compute_CustomErrorResponsePolicyCustomErrorResponseRule');
