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

namespace Google\Service\Directory;

class ByteUsage extends \Google\Model
{
  /**
   * @var string
   */
  public $capacityBytes;
  /**
   * @var string
   */
  public $usedBytes;

  /**
   * @param string
   */
  public function setCapacityBytes($capacityBytes)
  {
    $this->capacityBytes = $capacityBytes;
  }
  /**
   * @return string
   */
  public function getCapacityBytes()
  {
    return $this->capacityBytes;
  }
  /**
   * @param string
   */
  public function setUsedBytes($usedBytes)
  {
    $this->usedBytes = $usedBytes;
  }
  /**
   * @return string
   */
  public function getUsedBytes()
  {
    return $this->usedBytes;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ByteUsage::class, 'Google_Service_Directory_ByteUsage');
