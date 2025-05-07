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

namespace Google\Service\Backupdr;

class RequirementOverride extends \Google\Model
{
  /**
   * @var string
   */
  public $ziOverride;
  /**
   * @var string
   */
  public $zsOverride;

  /**
   * @param string
   */
  public function setZiOverride($ziOverride)
  {
    $this->ziOverride = $ziOverride;
  }
  /**
   * @return string
   */
  public function getZiOverride()
  {
    return $this->ziOverride;
  }
  /**
   * @param string
   */
  public function setZsOverride($zsOverride)
  {
    $this->zsOverride = $zsOverride;
  }
  /**
   * @return string
   */
  public function getZsOverride()
  {
    return $this->zsOverride;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RequirementOverride::class, 'Google_Service_Backupdr_RequirementOverride');
