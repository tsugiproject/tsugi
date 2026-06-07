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

namespace Google\Service\DeploymentManager;

class GetVersionOperationMetadataSbomInfo extends \Google\Model
{
  /**
   * SBOM versions currently applied to the resource. The key is the component
   * name and the value is the version.
   *
   * @var string[]
   */
  public $currentComponentVersions;
  /**
   * SBOM versions scheduled for the next maintenance. The key is the component
   * name and the value is the version.
   *
   * @var string[]
   */
  public $targetComponentVersions;

  /**
   * SBOM versions currently applied to the resource. The key is the component
   * name and the value is the version.
   *
   * @param string[] $currentComponentVersions
   */
  public function setCurrentComponentVersions($currentComponentVersions)
  {
    $this->currentComponentVersions = $currentComponentVersions;
  }
  /**
   * @return string[]
   */
  public function getCurrentComponentVersions()
  {
    return $this->currentComponentVersions;
  }
  /**
   * SBOM versions scheduled for the next maintenance. The key is the component
   * name and the value is the version.
   *
   * @param string[] $targetComponentVersions
   */
  public function setTargetComponentVersions($targetComponentVersions)
  {
    $this->targetComponentVersions = $targetComponentVersions;
  }
  /**
   * @return string[]
   */
  public function getTargetComponentVersions()
  {
    return $this->targetComponentVersions;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GetVersionOperationMetadataSbomInfo::class, 'Google_Service_DeploymentManager_GetVersionOperationMetadataSbomInfo');
