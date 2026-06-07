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

class CrashLoopBackOffConfig extends \Google\Model
{
  /**
   * Optional. The maximum duration the backoff delay can accrue to for
   * container restarts, minimum 1 second, maximum 300 seconds. If not set,
   * defaults to the internal crashloopbackoff maximum. The string must be a
   * sequence of decimal numbers, each with optional fraction and a unit suffix,
   * such as "300ms". Valid time units are "ns", "us" (or "µs"), "ms", "s", "m",
   * "h". See https://kubernetes.io/docs/concepts/workloads/pods/pod-
   * lifecycle/#configurable-container-restart-delay for more details.
   *
   * @var string
   */
  public $maxContainerRestartPeriod;

  /**
   * Optional. The maximum duration the backoff delay can accrue to for
   * container restarts, minimum 1 second, maximum 300 seconds. If not set,
   * defaults to the internal crashloopbackoff maximum. The string must be a
   * sequence of decimal numbers, each with optional fraction and a unit suffix,
   * such as "300ms". Valid time units are "ns", "us" (or "µs"), "ms", "s", "m",
   * "h". See https://kubernetes.io/docs/concepts/workloads/pods/pod-
   * lifecycle/#configurable-container-restart-delay for more details.
   *
   * @param string $maxContainerRestartPeriod
   */
  public function setMaxContainerRestartPeriod($maxContainerRestartPeriod)
  {
    $this->maxContainerRestartPeriod = $maxContainerRestartPeriod;
  }
  /**
   * @return string
   */
  public function getMaxContainerRestartPeriod()
  {
    return $this->maxContainerRestartPeriod;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CrashLoopBackOffConfig::class, 'Google_Service_Container_CrashLoopBackOffConfig');
