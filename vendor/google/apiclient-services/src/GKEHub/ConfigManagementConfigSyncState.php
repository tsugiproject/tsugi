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

namespace Google\Service\GKEHub;

class ConfigManagementConfigSyncState extends \Google\Collection
{
  protected $collection_key = 'errors';
  /**
   * @var string
   */
  public $clusterLevelStopSyncingState;
  /**
   * @var int
   */
  public $crCount;
  protected $deploymentStateType = ConfigManagementConfigSyncDeploymentState::class;
  protected $deploymentStateDataType = '';
  protected $errorsType = ConfigManagementConfigSyncError::class;
  protected $errorsDataType = 'array';
  /**
   * @var string
   */
  public $reposyncCrd;
  /**
   * @var string
   */
  public $rootsyncCrd;
  /**
   * @var string
   */
  public $state;
  protected $syncStateType = ConfigManagementSyncState::class;
  protected $syncStateDataType = '';
  protected $versionType = ConfigManagementConfigSyncVersion::class;
  protected $versionDataType = '';

  /**
   * @param string
   */
  public function setClusterLevelStopSyncingState($clusterLevelStopSyncingState)
  {
    $this->clusterLevelStopSyncingState = $clusterLevelStopSyncingState;
  }
  /**
   * @return string
   */
  public function getClusterLevelStopSyncingState()
  {
    return $this->clusterLevelStopSyncingState;
  }
  /**
   * @param int
   */
  public function setCrCount($crCount)
  {
    $this->crCount = $crCount;
  }
  /**
   * @return int
   */
  public function getCrCount()
  {
    return $this->crCount;
  }
  /**
   * @param ConfigManagementConfigSyncDeploymentState
   */
  public function setDeploymentState(ConfigManagementConfigSyncDeploymentState $deploymentState)
  {
    $this->deploymentState = $deploymentState;
  }
  /**
   * @return ConfigManagementConfigSyncDeploymentState
   */
  public function getDeploymentState()
  {
    return $this->deploymentState;
  }
  /**
   * @param ConfigManagementConfigSyncError[]
   */
  public function setErrors($errors)
  {
    $this->errors = $errors;
  }
  /**
   * @return ConfigManagementConfigSyncError[]
   */
  public function getErrors()
  {
    return $this->errors;
  }
  /**
   * @param string
   */
  public function setReposyncCrd($reposyncCrd)
  {
    $this->reposyncCrd = $reposyncCrd;
  }
  /**
   * @return string
   */
  public function getReposyncCrd()
  {
    return $this->reposyncCrd;
  }
  /**
   * @param string
   */
  public function setRootsyncCrd($rootsyncCrd)
  {
    $this->rootsyncCrd = $rootsyncCrd;
  }
  /**
   * @return string
   */
  public function getRootsyncCrd()
  {
    return $this->rootsyncCrd;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * @param ConfigManagementSyncState
   */
  public function setSyncState(ConfigManagementSyncState $syncState)
  {
    $this->syncState = $syncState;
  }
  /**
   * @return ConfigManagementSyncState
   */
  public function getSyncState()
  {
    return $this->syncState;
  }
  /**
   * @param ConfigManagementConfigSyncVersion
   */
  public function setVersion(ConfigManagementConfigSyncVersion $version)
  {
    $this->version = $version;
  }
  /**
   * @return ConfigManagementConfigSyncVersion
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ConfigManagementConfigSyncState::class, 'Google_Service_GKEHub_ConfigManagementConfigSyncState');
