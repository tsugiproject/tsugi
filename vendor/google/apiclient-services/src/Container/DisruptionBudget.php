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

namespace Google\Service\Container;

class DisruptionBudget extends \Google\Model
{
  /**
   * Output only. The last time a disruption was performed on the control plane.
   *
   * @var string
   */
  public $lastDisruptionTime;
  /**
   * Output only. The last time a minor version upgrade was performed on the
   * control plane.
   *
   * @var string
   */
  public $lastMinorVersionDisruptionTime;
  /**
   * Optional. The minimum duration between two minor version upgrades of the
   * control plane.
   *
   * @var string
   */
  public $minorVersionDisruptionInterval;
  /**
   * Optional. The minimum duration between two patch version upgrades of the
   * control plane.
   *
   * @var string
   */
  public $patchVersionDisruptionInterval;

  /**
   * Output only. The last time a disruption was performed on the control plane.
   *
   * @param string $lastDisruptionTime
   */
  public function setLastDisruptionTime($lastDisruptionTime)
  {
    $this->lastDisruptionTime = $lastDisruptionTime;
  }
  /**
   * @return string
   */
  public function getLastDisruptionTime()
  {
    return $this->lastDisruptionTime;
  }
  /**
   * Output only. The last time a minor version upgrade was performed on the
   * control plane.
   *
   * @param string $lastMinorVersionDisruptionTime
   */
  public function setLastMinorVersionDisruptionTime($lastMinorVersionDisruptionTime)
  {
    $this->lastMinorVersionDisruptionTime = $lastMinorVersionDisruptionTime;
  }
  /**
   * @return string
   */
  public function getLastMinorVersionDisruptionTime()
  {
    return $this->lastMinorVersionDisruptionTime;
  }
  /**
   * Optional. The minimum duration between two minor version upgrades of the
   * control plane.
   *
   * @param string $minorVersionDisruptionInterval
   */
  public function setMinorVersionDisruptionInterval($minorVersionDisruptionInterval)
  {
    $this->minorVersionDisruptionInterval = $minorVersionDisruptionInterval;
  }
  /**
   * @return string
   */
  public function getMinorVersionDisruptionInterval()
  {
    return $this->minorVersionDisruptionInterval;
  }
  /**
   * Optional. The minimum duration between two patch version upgrades of the
   * control plane.
   *
   * @param string $patchVersionDisruptionInterval
   */
  public function setPatchVersionDisruptionInterval($patchVersionDisruptionInterval)
  {
    $this->patchVersionDisruptionInterval = $patchVersionDisruptionInterval;
  }
  /**
   * @return string
   */
  public function getPatchVersionDisruptionInterval()
  {
    return $this->patchVersionDisruptionInterval;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DisruptionBudget::class, 'Google_Service_Container_DisruptionBudget');
