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

namespace Google\Service\Apigee;

class GoogleCloudApigeeV1Environment extends \Google\Model
{
  /**
   * @var string
   */
  public $apiProxyType;
  protected $clientIpResolutionConfigType = GoogleCloudApigeeV1EnvironmentClientIPResolutionConfig::class;
  protected $clientIpResolutionConfigDataType = '';
  /**
   * @var string
   */
  public $createdAt;
  /**
   * @var string
   */
  public $deploymentType;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $displayName;
  /**
   * @var string
   */
  public $forwardProxyUri;
  /**
   * @var bool
   */
  public $hasAttachedFlowHooks;
  /**
   * @var string
   */
  public $lastModifiedAt;
  /**
   * @var string
   */
  public $name;
  protected $nodeConfigType = GoogleCloudApigeeV1NodeConfig::class;
  protected $nodeConfigDataType = '';
  protected $propertiesType = GoogleCloudApigeeV1Properties::class;
  protected $propertiesDataType = '';
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $type;

  /**
   * @param string
   */
  public function setApiProxyType($apiProxyType)
  {
    $this->apiProxyType = $apiProxyType;
  }
  /**
   * @return string
   */
  public function getApiProxyType()
  {
    return $this->apiProxyType;
  }
  /**
   * @param GoogleCloudApigeeV1EnvironmentClientIPResolutionConfig
   */
  public function setClientIpResolutionConfig(GoogleCloudApigeeV1EnvironmentClientIPResolutionConfig $clientIpResolutionConfig)
  {
    $this->clientIpResolutionConfig = $clientIpResolutionConfig;
  }
  /**
   * @return GoogleCloudApigeeV1EnvironmentClientIPResolutionConfig
   */
  public function getClientIpResolutionConfig()
  {
    return $this->clientIpResolutionConfig;
  }
  /**
   * @param string
   */
  public function setCreatedAt($createdAt)
  {
    $this->createdAt = $createdAt;
  }
  /**
   * @return string
   */
  public function getCreatedAt()
  {
    return $this->createdAt;
  }
  /**
   * @param string
   */
  public function setDeploymentType($deploymentType)
  {
    $this->deploymentType = $deploymentType;
  }
  /**
   * @return string
   */
  public function getDeploymentType()
  {
    return $this->deploymentType;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
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
  public function setForwardProxyUri($forwardProxyUri)
  {
    $this->forwardProxyUri = $forwardProxyUri;
  }
  /**
   * @return string
   */
  public function getForwardProxyUri()
  {
    return $this->forwardProxyUri;
  }
  /**
   * @param bool
   */
  public function setHasAttachedFlowHooks($hasAttachedFlowHooks)
  {
    $this->hasAttachedFlowHooks = $hasAttachedFlowHooks;
  }
  /**
   * @return bool
   */
  public function getHasAttachedFlowHooks()
  {
    return $this->hasAttachedFlowHooks;
  }
  /**
   * @param string
   */
  public function setLastModifiedAt($lastModifiedAt)
  {
    $this->lastModifiedAt = $lastModifiedAt;
  }
  /**
   * @return string
   */
  public function getLastModifiedAt()
  {
    return $this->lastModifiedAt;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param GoogleCloudApigeeV1NodeConfig
   */
  public function setNodeConfig(GoogleCloudApigeeV1NodeConfig $nodeConfig)
  {
    $this->nodeConfig = $nodeConfig;
  }
  /**
   * @return GoogleCloudApigeeV1NodeConfig
   */
  public function getNodeConfig()
  {
    return $this->nodeConfig;
  }
  /**
   * @param GoogleCloudApigeeV1Properties
   */
  public function setProperties(GoogleCloudApigeeV1Properties $properties)
  {
    $this->properties = $properties;
  }
  /**
   * @return GoogleCloudApigeeV1Properties
   */
  public function getProperties()
  {
    return $this->properties;
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
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudApigeeV1Environment::class, 'Google_Service_Apigee_GoogleCloudApigeeV1Environment');
