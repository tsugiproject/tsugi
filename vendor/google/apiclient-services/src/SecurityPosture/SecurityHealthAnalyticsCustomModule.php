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

namespace Google\Service\SecurityPosture;

class SecurityHealthAnalyticsCustomModule extends \Google\Model
{
  protected $configType = CustomConfig::class;
  protected $configDataType = '';
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $id;
  /**
   * @var string
   */
  public $moduleEnablementState;

  /**
   * @param CustomConfig
   */
  public function setConfig(CustomConfig $config)
  {
    $this->config = $config;
  }
  /**
   * @return CustomConfig
   */
  public function getConfig()
  {
    return $this->config;
  }
  /**
   * @param string
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param string
   */
  public function setModuleEnablementState($moduleEnablementState)
  {
    $this->moduleEnablementState = $moduleEnablementState;
  }
  /**
   * @return string
   */
  public function getModuleEnablementState()
  {
    return $this->moduleEnablementState;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SecurityHealthAnalyticsCustomModule::class, 'Google_Service_SecurityPosture_SecurityHealthAnalyticsCustomModule');
