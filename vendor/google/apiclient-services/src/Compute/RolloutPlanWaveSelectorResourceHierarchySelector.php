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

namespace Google\Service\Compute;

class RolloutPlanWaveSelectorResourceHierarchySelector extends \Google\Collection
{
  protected $collection_key = 'includedProjects';
  /**
   * Optional. Format: "folders/{folder_id}"
   *
   * @var string[]
   */
  public $includedFolders;
  /**
   * Optional. Format: "organizations/{organization_id}"
   *
   * @var string[]
   */
  public $includedOrganizations;
  /**
   * Optional. Format: "projects/{project_id}"
   *
   * @var string[]
   */
  public $includedProjects;

  /**
   * Optional. Format: "folders/{folder_id}"
   *
   * @param string[] $includedFolders
   */
  public function setIncludedFolders($includedFolders)
  {
    $this->includedFolders = $includedFolders;
  }
  /**
   * @return string[]
   */
  public function getIncludedFolders()
  {
    return $this->includedFolders;
  }
  /**
   * Optional. Format: "organizations/{organization_id}"
   *
   * @param string[] $includedOrganizations
   */
  public function setIncludedOrganizations($includedOrganizations)
  {
    $this->includedOrganizations = $includedOrganizations;
  }
  /**
   * @return string[]
   */
  public function getIncludedOrganizations()
  {
    return $this->includedOrganizations;
  }
  /**
   * Optional. Format: "projects/{project_id}"
   *
   * @param string[] $includedProjects
   */
  public function setIncludedProjects($includedProjects)
  {
    $this->includedProjects = $includedProjects;
  }
  /**
   * @return string[]
   */
  public function getIncludedProjects()
  {
    return $this->includedProjects;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlanWaveSelectorResourceHierarchySelector::class, 'Google_Service_Compute_RolloutPlanWaveSelectorResourceHierarchySelector');
