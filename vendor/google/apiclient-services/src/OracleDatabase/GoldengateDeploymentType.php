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

namespace Google\Service\OracleDatabase;

class GoldengateDeploymentType extends \Google\Collection
{
  /**
   * Default unspecified value.
   */
  public const CATEGORY_DEPLOYMENT_CATEGORY_UNSPECIFIED = 'DEPLOYMENT_CATEGORY_UNSPECIFIED';
  /**
   * Goldengate Deployment Type category is DATA_REPLICATION_CATEGORY.
   */
  public const CATEGORY_DATA_REPLICATION_CATEGORY = 'DATA_REPLICATION_CATEGORY';
  /**
   * Goldengate Deployment Type category is DATA_TRANSFORMS_CATEGORY.
   */
  public const CATEGORY_DATA_TRANSFORMS_CATEGORY = 'DATA_TRANSFORMS_CATEGORY';
  /**
   * Default unspecified value.
   */
  public const DEPLOYMENT_TYPE_DEPLOYMENT_TYPE_UNSPECIFIED = 'DEPLOYMENT_TYPE_UNSPECIFIED';
  /**
   * Goldengate Deployment Type category is OGG.
   */
  public const DEPLOYMENT_TYPE_OGG = 'OGG';
  /**
   * Goldengate Deployment Type category is DATABASE_ORACLE.
   */
  public const DEPLOYMENT_TYPE_DATABASE_ORACLE = 'DATABASE_ORACLE';
  /**
   * Goldengate Deployment Type category is BIGDATA.
   */
  public const DEPLOYMENT_TYPE_BIGDATA = 'BIGDATA';
  /**
   * Goldengate Deployment Type category is DATABASE_MICROSOFT_SQLSERVER.
   */
  public const DEPLOYMENT_TYPE_DATABASE_MICROSOFT_SQLSERVER = 'DATABASE_MICROSOFT_SQLSERVER';
  /**
   * Goldengate Deployment Type category is DATABASE_MYSQL.
   */
  public const DEPLOYMENT_TYPE_DATABASE_MYSQL = 'DATABASE_MYSQL';
  /**
   * Goldengate Deployment Type category is DATABASE_POSTGRESQL.
   */
  public const DEPLOYMENT_TYPE_DATABASE_POSTGRESQL = 'DATABASE_POSTGRESQL';
  /**
   * Goldengate Deployment Type category is DATABASE_DB2ZOS.
   */
  public const DEPLOYMENT_TYPE_DATABASE_DB2ZOS = 'DATABASE_DB2ZOS';
  /**
   * Goldengate Deployment Type category is DATABASE_DB2I.
   */
  public const DEPLOYMENT_TYPE_DATABASE_DB2I = 'DATABASE_DB2I';
  /**
   * Goldengate Deployment Type category is GGSA.
   */
  public const DEPLOYMENT_TYPE_GGSA = 'GGSA';
  /**
   * Goldengate Deployment Type category is DATA_TRANSFORMS.
   */
  public const DEPLOYMENT_TYPE_DATA_TRANSFORMS = 'DATA_TRANSFORMS';
  protected $collection_key = 'targetTechnologies';
  /**
   * Output only. The category of the Goldengate Deployment Type resource.
   *
   * @var string
   */
  public $category;
  /**
   * Output only. The connection types of the Goldengate Deployment Type
   * resource.
   *
   * @var string[]
   */
  public $connectionTypes;
  /**
   * Output only. The default username of the Goldengate Deployment Type
   * resource.
   *
   * @var string
   */
  public $defaultUsername;
  /**
   * Output only. The deployment type of the Goldengate Deployment Type
   * resource.
   *
   * @var string
   */
  public $deploymentType;
  /**
   * Output only. The display name of the Goldengate Deployment Type resource.
   *
   * @var string
   */
  public $displayName;
  /**
   * Identifier. The name of the Goldengate Deployment Type resource with the
   * format: projects/{project}/locations/{region}/goldengateDeploymentTypes/{go
   * ldengate_deployment_type}
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The Ogg version of the Goldengate Deployment Type resource.
   *
   * @var string
   */
  public $oggVersion;
  /**
   * Output only. The source technologies of the Goldengate Deployment Type
   * resource.
   *
   * @var string[]
   */
  public $sourceTechnologies;
  /**
   * Output only. The supported capabilities of the Goldengate Deployment Type
   * resource.
   *
   * @var string[]
   */
  public $supportedCapabilities;
  /**
   * Output only. The supported technologies URL of the Goldengate Deployment
   * Type resource.
   *
   * @var string
   */
  public $supportedTechnologiesUrl;
  /**
   * Output only. The target technologies of the Goldengate Deployment Type
   * resource.
   *
   * @var string[]
   */
  public $targetTechnologies;

  /**
   * Output only. The category of the Goldengate Deployment Type resource.
   *
   * Accepted values: DEPLOYMENT_CATEGORY_UNSPECIFIED,
   * DATA_REPLICATION_CATEGORY, DATA_TRANSFORMS_CATEGORY
   *
   * @param self::CATEGORY_* $category
   */
  public function setCategory($category)
  {
    $this->category = $category;
  }
  /**
   * @return self::CATEGORY_*
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * Output only. The connection types of the Goldengate Deployment Type
   * resource.
   *
   * @param string[] $connectionTypes
   */
  public function setConnectionTypes($connectionTypes)
  {
    $this->connectionTypes = $connectionTypes;
  }
  /**
   * @return string[]
   */
  public function getConnectionTypes()
  {
    return $this->connectionTypes;
  }
  /**
   * Output only. The default username of the Goldengate Deployment Type
   * resource.
   *
   * @param string $defaultUsername
   */
  public function setDefaultUsername($defaultUsername)
  {
    $this->defaultUsername = $defaultUsername;
  }
  /**
   * @return string
   */
  public function getDefaultUsername()
  {
    return $this->defaultUsername;
  }
  /**
   * Output only. The deployment type of the Goldengate Deployment Type
   * resource.
   *
   * Accepted values: DEPLOYMENT_TYPE_UNSPECIFIED, OGG, DATABASE_ORACLE,
   * BIGDATA, DATABASE_MICROSOFT_SQLSERVER, DATABASE_MYSQL, DATABASE_POSTGRESQL,
   * DATABASE_DB2ZOS, DATABASE_DB2I, GGSA, DATA_TRANSFORMS
   *
   * @param self::DEPLOYMENT_TYPE_* $deploymentType
   */
  public function setDeploymentType($deploymentType)
  {
    $this->deploymentType = $deploymentType;
  }
  /**
   * @return self::DEPLOYMENT_TYPE_*
   */
  public function getDeploymentType()
  {
    return $this->deploymentType;
  }
  /**
   * Output only. The display name of the Goldengate Deployment Type resource.
   *
   * @param string $displayName
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
   * Identifier. The name of the Goldengate Deployment Type resource with the
   * format: projects/{project}/locations/{region}/goldengateDeploymentTypes/{go
   * ldengate_deployment_type}
   *
   * @param string $name
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
   * Output only. The Ogg version of the Goldengate Deployment Type resource.
   *
   * @param string $oggVersion
   */
  public function setOggVersion($oggVersion)
  {
    $this->oggVersion = $oggVersion;
  }
  /**
   * @return string
   */
  public function getOggVersion()
  {
    return $this->oggVersion;
  }
  /**
   * Output only. The source technologies of the Goldengate Deployment Type
   * resource.
   *
   * @param string[] $sourceTechnologies
   */
  public function setSourceTechnologies($sourceTechnologies)
  {
    $this->sourceTechnologies = $sourceTechnologies;
  }
  /**
   * @return string[]
   */
  public function getSourceTechnologies()
  {
    return $this->sourceTechnologies;
  }
  /**
   * Output only. The supported capabilities of the Goldengate Deployment Type
   * resource.
   *
   * @param string[] $supportedCapabilities
   */
  public function setSupportedCapabilities($supportedCapabilities)
  {
    $this->supportedCapabilities = $supportedCapabilities;
  }
  /**
   * @return string[]
   */
  public function getSupportedCapabilities()
  {
    return $this->supportedCapabilities;
  }
  /**
   * Output only. The supported technologies URL of the Goldengate Deployment
   * Type resource.
   *
   * @param string $supportedTechnologiesUrl
   */
  public function setSupportedTechnologiesUrl($supportedTechnologiesUrl)
  {
    $this->supportedTechnologiesUrl = $supportedTechnologiesUrl;
  }
  /**
   * @return string
   */
  public function getSupportedTechnologiesUrl()
  {
    return $this->supportedTechnologiesUrl;
  }
  /**
   * Output only. The target technologies of the Goldengate Deployment Type
   * resource.
   *
   * @param string[] $targetTechnologies
   */
  public function setTargetTechnologies($targetTechnologies)
  {
    $this->targetTechnologies = $targetTechnologies;
  }
  /**
   * @return string[]
   */
  public function getTargetTechnologies()
  {
    return $this->targetTechnologies;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDeploymentType::class, 'Google_Service_OracleDatabase_GoldengateDeploymentType');
