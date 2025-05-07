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

class Scheduling extends \Google\Collection
{
  protected $collection_key = 'nodeAffinities';
  /**
   * @var bool
   */
  public $automaticRestart;
  /**
   * @var int
   */
  public $availabilityDomain;
  /**
   * @var int
   */
  public $hostErrorTimeoutSeconds;
  /**
   * @var string
   */
  public $instanceTerminationAction;
  protected $localSsdRecoveryTimeoutType = Duration::class;
  protected $localSsdRecoveryTimeoutDataType = '';
  /**
   * @var string
   */
  public $locationHint;
  protected $maxRunDurationType = Duration::class;
  protected $maxRunDurationDataType = '';
  /**
   * @var int
   */
  public $minNodeCpus;
  protected $nodeAffinitiesType = SchedulingNodeAffinity::class;
  protected $nodeAffinitiesDataType = 'array';
  /**
   * @var string
   */
  public $onHostMaintenance;
  protected $onInstanceStopActionType = SchedulingOnInstanceStopAction::class;
  protected $onInstanceStopActionDataType = '';
  /**
   * @var bool
   */
  public $preemptible;
  /**
   * @var string
   */
  public $provisioningModel;
  /**
   * @var string
   */
  public $terminationTime;

  /**
   * @param bool
   */
  public function setAutomaticRestart($automaticRestart)
  {
    $this->automaticRestart = $automaticRestart;
  }
  /**
   * @return bool
   */
  public function getAutomaticRestart()
  {
    return $this->automaticRestart;
  }
  /**
   * @param int
   */
  public function setAvailabilityDomain($availabilityDomain)
  {
    $this->availabilityDomain = $availabilityDomain;
  }
  /**
   * @return int
   */
  public function getAvailabilityDomain()
  {
    return $this->availabilityDomain;
  }
  /**
   * @param int
   */
  public function setHostErrorTimeoutSeconds($hostErrorTimeoutSeconds)
  {
    $this->hostErrorTimeoutSeconds = $hostErrorTimeoutSeconds;
  }
  /**
   * @return int
   */
  public function getHostErrorTimeoutSeconds()
  {
    return $this->hostErrorTimeoutSeconds;
  }
  /**
   * @param string
   */
  public function setInstanceTerminationAction($instanceTerminationAction)
  {
    $this->instanceTerminationAction = $instanceTerminationAction;
  }
  /**
   * @return string
   */
  public function getInstanceTerminationAction()
  {
    return $this->instanceTerminationAction;
  }
  /**
   * @param Duration
   */
  public function setLocalSsdRecoveryTimeout(Duration $localSsdRecoveryTimeout)
  {
    $this->localSsdRecoveryTimeout = $localSsdRecoveryTimeout;
  }
  /**
   * @return Duration
   */
  public function getLocalSsdRecoveryTimeout()
  {
    return $this->localSsdRecoveryTimeout;
  }
  /**
   * @param string
   */
  public function setLocationHint($locationHint)
  {
    $this->locationHint = $locationHint;
  }
  /**
   * @return string
   */
  public function getLocationHint()
  {
    return $this->locationHint;
  }
  /**
   * @param Duration
   */
  public function setMaxRunDuration(Duration $maxRunDuration)
  {
    $this->maxRunDuration = $maxRunDuration;
  }
  /**
   * @return Duration
   */
  public function getMaxRunDuration()
  {
    return $this->maxRunDuration;
  }
  /**
   * @param int
   */
  public function setMinNodeCpus($minNodeCpus)
  {
    $this->minNodeCpus = $minNodeCpus;
  }
  /**
   * @return int
   */
  public function getMinNodeCpus()
  {
    return $this->minNodeCpus;
  }
  /**
   * @param SchedulingNodeAffinity[]
   */
  public function setNodeAffinities($nodeAffinities)
  {
    $this->nodeAffinities = $nodeAffinities;
  }
  /**
   * @return SchedulingNodeAffinity[]
   */
  public function getNodeAffinities()
  {
    return $this->nodeAffinities;
  }
  /**
   * @param string
   */
  public function setOnHostMaintenance($onHostMaintenance)
  {
    $this->onHostMaintenance = $onHostMaintenance;
  }
  /**
   * @return string
   */
  public function getOnHostMaintenance()
  {
    return $this->onHostMaintenance;
  }
  /**
   * @param SchedulingOnInstanceStopAction
   */
  public function setOnInstanceStopAction(SchedulingOnInstanceStopAction $onInstanceStopAction)
  {
    $this->onInstanceStopAction = $onInstanceStopAction;
  }
  /**
   * @return SchedulingOnInstanceStopAction
   */
  public function getOnInstanceStopAction()
  {
    return $this->onInstanceStopAction;
  }
  /**
   * @param bool
   */
  public function setPreemptible($preemptible)
  {
    $this->preemptible = $preemptible;
  }
  /**
   * @return bool
   */
  public function getPreemptible()
  {
    return $this->preemptible;
  }
  /**
   * @param string
   */
  public function setProvisioningModel($provisioningModel)
  {
    $this->provisioningModel = $provisioningModel;
  }
  /**
   * @return string
   */
  public function getProvisioningModel()
  {
    return $this->provisioningModel;
  }
  /**
   * @param string
   */
  public function setTerminationTime($terminationTime)
  {
    $this->terminationTime = $terminationTime;
  }
  /**
   * @return string
   */
  public function getTerminationTime()
  {
    return $this->terminationTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Scheduling::class, 'Google_Service_Compute_Scheduling');
