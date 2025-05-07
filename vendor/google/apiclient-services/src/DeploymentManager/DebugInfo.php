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

namespace Google\Service\DeploymentManager;

class DebugInfo extends \Google\Collection
{
  protected $collection_key = 'stackEntries';
  /**
   * @var string
   */
  public $detail;
  /**
   * @var string[]
   */
  public $stackEntries;

  /**
   * @param string
   */
  public function setDetail($detail)
  {
    $this->detail = $detail;
  }
  /**
   * @return string
   */
  public function getDetail()
  {
    return $this->detail;
  }
  /**
   * @param string[]
   */
  public function setStackEntries($stackEntries)
  {
    $this->stackEntries = $stackEntries;
  }
  /**
   * @return string[]
   */
  public function getStackEntries()
  {
    return $this->stackEntries;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DebugInfo::class, 'Google_Service_DeploymentManager_DebugInfo');
