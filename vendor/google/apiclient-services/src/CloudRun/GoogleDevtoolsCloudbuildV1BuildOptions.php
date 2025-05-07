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

namespace Google\Service\CloudRun;

class GoogleDevtoolsCloudbuildV1BuildOptions extends \Google\Collection
{
  protected $collection_key = 'volumes';
  /**
   * @var bool
   */
  public $automapSubstitutions;
  /**
   * @var string
   */
  public $defaultLogsBucketBehavior;
  /**
   * @var string
   */
  public $diskSizeGb;
  /**
   * @var bool
   */
  public $dynamicSubstitutions;
  /**
   * @var bool
   */
  public $enableStructuredLogging;
  /**
   * @var string[]
   */
  public $env;
  /**
   * @var string
   */
  public $logStreamingOption;
  /**
   * @var string
   */
  public $logging;
  /**
   * @var string
   */
  public $machineType;
  protected $poolType = GoogleDevtoolsCloudbuildV1PoolOption::class;
  protected $poolDataType = '';
  /**
   * @var string
   */
  public $pubsubTopic;
  /**
   * @var string
   */
  public $requestedVerifyOption;
  /**
   * @var string[]
   */
  public $secretEnv;
  /**
   * @var string[]
   */
  public $sourceProvenanceHash;
  /**
   * @var string
   */
  public $substitutionOption;
  protected $volumesType = GoogleDevtoolsCloudbuildV1Volume::class;
  protected $volumesDataType = 'array';
  /**
   * @var string
   */
  public $workerPool;

  /**
   * @param bool
   */
  public function setAutomapSubstitutions($automapSubstitutions)
  {
    $this->automapSubstitutions = $automapSubstitutions;
  }
  /**
   * @return bool
   */
  public function getAutomapSubstitutions()
  {
    return $this->automapSubstitutions;
  }
  /**
   * @param string
   */
  public function setDefaultLogsBucketBehavior($defaultLogsBucketBehavior)
  {
    $this->defaultLogsBucketBehavior = $defaultLogsBucketBehavior;
  }
  /**
   * @return string
   */
  public function getDefaultLogsBucketBehavior()
  {
    return $this->defaultLogsBucketBehavior;
  }
  /**
   * @param string
   */
  public function setDiskSizeGb($diskSizeGb)
  {
    $this->diskSizeGb = $diskSizeGb;
  }
  /**
   * @return string
   */
  public function getDiskSizeGb()
  {
    return $this->diskSizeGb;
  }
  /**
   * @param bool
   */
  public function setDynamicSubstitutions($dynamicSubstitutions)
  {
    $this->dynamicSubstitutions = $dynamicSubstitutions;
  }
  /**
   * @return bool
   */
  public function getDynamicSubstitutions()
  {
    return $this->dynamicSubstitutions;
  }
  /**
   * @param bool
   */
  public function setEnableStructuredLogging($enableStructuredLogging)
  {
    $this->enableStructuredLogging = $enableStructuredLogging;
  }
  /**
   * @return bool
   */
  public function getEnableStructuredLogging()
  {
    return $this->enableStructuredLogging;
  }
  /**
   * @param string[]
   */
  public function setEnv($env)
  {
    $this->env = $env;
  }
  /**
   * @return string[]
   */
  public function getEnv()
  {
    return $this->env;
  }
  /**
   * @param string
   */
  public function setLogStreamingOption($logStreamingOption)
  {
    $this->logStreamingOption = $logStreamingOption;
  }
  /**
   * @return string
   */
  public function getLogStreamingOption()
  {
    return $this->logStreamingOption;
  }
  /**
   * @param string
   */
  public function setLogging($logging)
  {
    $this->logging = $logging;
  }
  /**
   * @return string
   */
  public function getLogging()
  {
    return $this->logging;
  }
  /**
   * @param string
   */
  public function setMachineType($machineType)
  {
    $this->machineType = $machineType;
  }
  /**
   * @return string
   */
  public function getMachineType()
  {
    return $this->machineType;
  }
  /**
   * @param GoogleDevtoolsCloudbuildV1PoolOption
   */
  public function setPool(GoogleDevtoolsCloudbuildV1PoolOption $pool)
  {
    $this->pool = $pool;
  }
  /**
   * @return GoogleDevtoolsCloudbuildV1PoolOption
   */
  public function getPool()
  {
    return $this->pool;
  }
  /**
   * @param string
   */
  public function setPubsubTopic($pubsubTopic)
  {
    $this->pubsubTopic = $pubsubTopic;
  }
  /**
   * @return string
   */
  public function getPubsubTopic()
  {
    return $this->pubsubTopic;
  }
  /**
   * @param string
   */
  public function setRequestedVerifyOption($requestedVerifyOption)
  {
    $this->requestedVerifyOption = $requestedVerifyOption;
  }
  /**
   * @return string
   */
  public function getRequestedVerifyOption()
  {
    return $this->requestedVerifyOption;
  }
  /**
   * @param string[]
   */
  public function setSecretEnv($secretEnv)
  {
    $this->secretEnv = $secretEnv;
  }
  /**
   * @return string[]
   */
  public function getSecretEnv()
  {
    return $this->secretEnv;
  }
  /**
   * @param string[]
   */
  public function setSourceProvenanceHash($sourceProvenanceHash)
  {
    $this->sourceProvenanceHash = $sourceProvenanceHash;
  }
  /**
   * @return string[]
   */
  public function getSourceProvenanceHash()
  {
    return $this->sourceProvenanceHash;
  }
  /**
   * @param string
   */
  public function setSubstitutionOption($substitutionOption)
  {
    $this->substitutionOption = $substitutionOption;
  }
  /**
   * @return string
   */
  public function getSubstitutionOption()
  {
    return $this->substitutionOption;
  }
  /**
   * @param GoogleDevtoolsCloudbuildV1Volume[]
   */
  public function setVolumes($volumes)
  {
    $this->volumes = $volumes;
  }
  /**
   * @return GoogleDevtoolsCloudbuildV1Volume[]
   */
  public function getVolumes()
  {
    return $this->volumes;
  }
  /**
   * @param string
   */
  public function setWorkerPool($workerPool)
  {
    $this->workerPool = $workerPool;
  }
  /**
   * @return string
   */
  public function getWorkerPool()
  {
    return $this->workerPool;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleDevtoolsCloudbuildV1BuildOptions::class, 'Google_Service_CloudRun_GoogleDevtoolsCloudbuildV1BuildOptions');
