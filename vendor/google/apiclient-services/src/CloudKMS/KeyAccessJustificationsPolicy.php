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

namespace Google\Service\CloudKMS;

class KeyAccessJustificationsPolicy extends \Google\Collection
{
  protected $collection_key = 'allowedAccessReasons';
  /**
   * @var string[]
   */
  public $allowedAccessReasons;

  /**
   * @param string[]
   */
  public function setAllowedAccessReasons($allowedAccessReasons)
  {
    $this->allowedAccessReasons = $allowedAccessReasons;
  }
  /**
   * @return string[]
   */
  public function getAllowedAccessReasons()
  {
    return $this->allowedAccessReasons;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(KeyAccessJustificationsPolicy::class, 'Google_Service_CloudKMS_KeyAccessJustificationsPolicy');
