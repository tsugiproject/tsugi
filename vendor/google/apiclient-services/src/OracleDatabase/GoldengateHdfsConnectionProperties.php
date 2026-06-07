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

class GoldengateHdfsConnectionProperties extends \Google\Model
{
  /**
   * Optional. The content of the Hadoop Distributed File System configuration
   * file (core-site.xml).
   *
   * @var string
   */
  public $coreSiteXml;
  /**
   * Optional. The technology type of HdfsConnection.
   *
   * @var string
   */
  public $technologyType;

  /**
   * Optional. The content of the Hadoop Distributed File System configuration
   * file (core-site.xml).
   *
   * @param string $coreSiteXml
   */
  public function setCoreSiteXml($coreSiteXml)
  {
    $this->coreSiteXml = $coreSiteXml;
  }
  /**
   * @return string
   */
  public function getCoreSiteXml()
  {
    return $this->coreSiteXml;
  }
  /**
   * Optional. The technology type of HdfsConnection.
   *
   * @param string $technologyType
   */
  public function setTechnologyType($technologyType)
  {
    $this->technologyType = $technologyType;
  }
  /**
   * @return string
   */
  public function getTechnologyType()
  {
    return $this->technologyType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateHdfsConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateHdfsConnectionProperties');
