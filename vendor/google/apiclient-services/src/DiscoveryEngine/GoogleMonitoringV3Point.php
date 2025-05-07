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

namespace Google\Service\DiscoveryEngine;

class GoogleMonitoringV3Point extends \Google\Model
{
  protected $intervalType = GoogleMonitoringV3TimeInterval::class;
  protected $intervalDataType = '';
  protected $valueType = GoogleMonitoringV3TypedValue::class;
  protected $valueDataType = '';

  /**
   * @param GoogleMonitoringV3TimeInterval
   */
  public function setInterval(GoogleMonitoringV3TimeInterval $interval)
  {
    $this->interval = $interval;
  }
  /**
   * @return GoogleMonitoringV3TimeInterval
   */
  public function getInterval()
  {
    return $this->interval;
  }
  /**
   * @param GoogleMonitoringV3TypedValue
   */
  public function setValue(GoogleMonitoringV3TypedValue $value)
  {
    $this->value = $value;
  }
  /**
   * @return GoogleMonitoringV3TypedValue
   */
  public function getValue()
  {
    return $this->value;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleMonitoringV3Point::class, 'Google_Service_DiscoveryEngine_GoogleMonitoringV3Point');
