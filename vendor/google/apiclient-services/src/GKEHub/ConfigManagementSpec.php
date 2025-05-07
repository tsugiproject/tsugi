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

class ConfigManagementSpec extends \Google\Model
{
  protected $binauthzType = ConfigManagementBinauthzConfig::class;
  protected $binauthzDataType = '';
  /**
   * @var string
   */
  public $cluster;
  protected $configSyncType = ConfigManagementConfigSync::class;
  protected $configSyncDataType = '';
  protected $hierarchyControllerType = ConfigManagementHierarchyControllerConfig::class;
  protected $hierarchyControllerDataType = '';
  /**
   * @var string
   */
  public $management;
  protected $policyControllerType = ConfigManagementPolicyController::class;
  protected $policyControllerDataType = '';
  /**
   * @var string
   */
  public $version;

  /**
   * @param ConfigManagementBinauthzConfig
   */
  public function setBinauthz(ConfigManagementBinauthzConfig $binauthz)
  {
    $this->binauthz = $binauthz;
  }
  /**
   * @return ConfigManagementBinauthzConfig
   */
  public function getBinauthz()
  {
    return $this->binauthz;
  }
  /**
   * @param string
   */
  public function setCluster($cluster)
  {
    $this->cluster = $cluster;
  }
  /**
   * @return string
   */
  public function getCluster()
  {
    return $this->cluster;
  }
  /**
   * @param ConfigManagementConfigSync
   */
  public function setConfigSync(ConfigManagementConfigSync $configSync)
  {
    $this->configSync = $configSync;
  }
  /**
   * @return ConfigManagementConfigSync
   */
  public function getConfigSync()
  {
    return $this->configSync;
  }
  /**
   * @param ConfigManagementHierarchyControllerConfig
   */
  public function setHierarchyController(ConfigManagementHierarchyControllerConfig $hierarchyController)
  {
    $this->hierarchyController = $hierarchyController;
  }
  /**
   * @return ConfigManagementHierarchyControllerConfig
   */
  public function getHierarchyController()
  {
    return $this->hierarchyController;
  }
  /**
   * @param string
   */
  public function setManagement($management)
  {
    $this->management = $management;
  }
  /**
   * @return string
   */
  public function getManagement()
  {
    return $this->management;
  }
  /**
   * @param ConfigManagementPolicyController
   */
  public function setPolicyController(ConfigManagementPolicyController $policyController)
  {
    $this->policyController = $policyController;
  }
  /**
   * @return ConfigManagementPolicyController
   */
  public function getPolicyController()
  {
    return $this->policyController;
  }
  /**
   * @param string
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ConfigManagementSpec::class, 'Google_Service_GKEHub_ConfigManagementSpec');
