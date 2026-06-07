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

class GoldengateDeploymentVersion extends \Google\Model
{
  /**
   * Identifier. The name of the Goldengate Deployment Version resource with the
   * format: projects/{project}/locations/{location}/goldengateDeploymentVersion
   * s/{goldengate_deployment_version}
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The deployment version ocid of the Goldengate Deployment
   * Version resource.
   *
   * @var string
   */
  public $ocid;
  protected $propertiesType = GoldengateDeploymentVersionProperties::class;
  protected $propertiesDataType = '';

  /**
   * Identifier. The name of the Goldengate Deployment Version resource with the
   * format: projects/{project}/locations/{location}/goldengateDeploymentVersion
   * s/{goldengate_deployment_version}
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
   * Output only. The deployment version ocid of the Goldengate Deployment
   * Version resource.
   *
   * @param string $ocid
   */
  public function setOcid($ocid)
  {
    $this->ocid = $ocid;
  }
  /**
   * @return string
   */
  public function getOcid()
  {
    return $this->ocid;
  }
  /**
   * Output only. The technology type of the Goldengate Deployment Version
   * resource.
   *
   * @param GoldengateDeploymentVersionProperties $properties
   */
  public function setProperties(GoldengateDeploymentVersionProperties $properties)
  {
    $this->properties = $properties;
  }
  /**
   * @return GoldengateDeploymentVersionProperties
   */
  public function getProperties()
  {
    return $this->properties;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDeploymentVersion::class, 'Google_Service_OracleDatabase_GoldengateDeploymentVersion');
