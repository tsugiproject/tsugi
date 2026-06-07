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

class GoldengateDeploymentVersionProperties extends \Google\Model
{
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
  /**
   * Default unspecified value.
   */
  public const RELEASE_TYPE_DEPLOYMENT_RELEASE_TYPE_UNSPECIFIED = 'DEPLOYMENT_RELEASE_TYPE_UNSPECIFIED';
  /**
   * Goldengate Deployment Version release type is MAJOR.
   */
  public const RELEASE_TYPE_MAJOR = 'MAJOR';
  /**
   * Goldengate Deployment Version release type is BUNDLE.
   */
  public const RELEASE_TYPE_BUNDLE = 'BUNDLE';
  /**
   * Goldengate Deployment Version release type is MINOR.
   */
  public const RELEASE_TYPE_MINOR = 'MINOR';
  /**
   * Output only. The deployment type of the Goldengate Deployment Version
   * resource.
   *
   * @var string
   */
  public $deploymentType;
  /**
   * Output only. The OGG version of the Goldengate Deployment Version resource.
   *
   * @var string
   */
  public $oggVersion;
  /**
   * Output only. The release time of the Goldengate Deployment Version
   * resource.
   *
   * @var string
   */
  public $releaseTime;
  /**
   * Output only. The release type of the Goldengate Deployment Version
   * resource.
   *
   * @var string
   */
  public $releaseType;
  /**
   * Optional. Whether the Goldengate Deployment Version resource is a security
   * fix.
   *
   * @var bool
   */
  public $securityFix;
  /**
   * Output only. The support end time of the Goldengate Deployment Version
   * resource.
   *
   * @var string
   */
  public $supportEndTime;

  /**
   * Output only. The deployment type of the Goldengate Deployment Version
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
   * Output only. The OGG version of the Goldengate Deployment Version resource.
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
   * Output only. The release time of the Goldengate Deployment Version
   * resource.
   *
   * @param string $releaseTime
   */
  public function setReleaseTime($releaseTime)
  {
    $this->releaseTime = $releaseTime;
  }
  /**
   * @return string
   */
  public function getReleaseTime()
  {
    return $this->releaseTime;
  }
  /**
   * Output only. The release type of the Goldengate Deployment Version
   * resource.
   *
   * Accepted values: DEPLOYMENT_RELEASE_TYPE_UNSPECIFIED, MAJOR, BUNDLE, MINOR
   *
   * @param self::RELEASE_TYPE_* $releaseType
   */
  public function setReleaseType($releaseType)
  {
    $this->releaseType = $releaseType;
  }
  /**
   * @return self::RELEASE_TYPE_*
   */
  public function getReleaseType()
  {
    return $this->releaseType;
  }
  /**
   * Optional. Whether the Goldengate Deployment Version resource is a security
   * fix.
   *
   * @param bool $securityFix
   */
  public function setSecurityFix($securityFix)
  {
    $this->securityFix = $securityFix;
  }
  /**
   * @return bool
   */
  public function getSecurityFix()
  {
    return $this->securityFix;
  }
  /**
   * Output only. The support end time of the Goldengate Deployment Version
   * resource.
   *
   * @param string $supportEndTime
   */
  public function setSupportEndTime($supportEndTime)
  {
    $this->supportEndTime = $supportEndTime;
  }
  /**
   * @return string
   */
  public function getSupportEndTime()
  {
    return $this->supportEndTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDeploymentVersionProperties::class, 'Google_Service_OracleDatabase_GoldengateDeploymentVersionProperties');
