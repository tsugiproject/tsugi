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

namespace Google\Service\OnDemandScanning;

class ComplianceOccurrence extends \Google\Collection
{
  protected $collection_key = 'nonCompliantFiles';
  /**
   * @var string
   */
  public $nonComplianceReason;
  protected $nonCompliantFilesType = NonCompliantFile::class;
  protected $nonCompliantFilesDataType = 'array';
  protected $versionType = ComplianceVersion::class;
  protected $versionDataType = '';

  /**
   * @param string
   */
  public function setNonComplianceReason($nonComplianceReason)
  {
    $this->nonComplianceReason = $nonComplianceReason;
  }
  /**
   * @return string
   */
  public function getNonComplianceReason()
  {
    return $this->nonComplianceReason;
  }
  /**
   * @param NonCompliantFile[]
   */
  public function setNonCompliantFiles($nonCompliantFiles)
  {
    $this->nonCompliantFiles = $nonCompliantFiles;
  }
  /**
   * @return NonCompliantFile[]
   */
  public function getNonCompliantFiles()
  {
    return $this->nonCompliantFiles;
  }
  /**
   * @param ComplianceVersion
   */
  public function setVersion(ComplianceVersion $version)
  {
    $this->version = $version;
  }
  /**
   * @return ComplianceVersion
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ComplianceOccurrence::class, 'Google_Service_OnDemandScanning_ComplianceOccurrence');
