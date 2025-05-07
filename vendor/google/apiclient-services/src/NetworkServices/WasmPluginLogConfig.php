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

namespace Google\Service\NetworkServices;

class WasmPluginLogConfig extends \Google\Model
{
  /**
   * @var bool
   */
  public $enable;
  /**
   * @var string
   */
  public $minLogLevel;
  /**
   * @var float
   */
  public $sampleRate;

  /**
   * @param bool
   */
  public function setEnable($enable)
  {
    $this->enable = $enable;
  }
  /**
   * @return bool
   */
  public function getEnable()
  {
    return $this->enable;
  }
  /**
   * @param string
   */
  public function setMinLogLevel($minLogLevel)
  {
    $this->minLogLevel = $minLogLevel;
  }
  /**
   * @return string
   */
  public function getMinLogLevel()
  {
    return $this->minLogLevel;
  }
  /**
   * @param float
   */
  public function setSampleRate($sampleRate)
  {
    $this->sampleRate = $sampleRate;
  }
  /**
   * @return float
   */
  public function getSampleRate()
  {
    return $this->sampleRate;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(WasmPluginLogConfig::class, 'Google_Service_NetworkServices_WasmPluginLogConfig');
