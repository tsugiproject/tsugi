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

class RolloutPlanWaveSelector extends \Google\Model
{
  protected $locationSelectorType = RolloutPlanWaveSelectorLocationSelector::class;
  protected $locationSelectorDataType = '';
  protected $resourceHierarchySelectorType = RolloutPlanWaveSelectorResourceHierarchySelector::class;
  protected $resourceHierarchySelectorDataType = '';

  /**
   * Optional. Roll out to resources by Cloud locations.
   *
   * @param RolloutPlanWaveSelectorLocationSelector $locationSelector
   */
  public function setLocationSelector(RolloutPlanWaveSelectorLocationSelector $locationSelector)
  {
    $this->locationSelector = $locationSelector;
  }
  /**
   * @return RolloutPlanWaveSelectorLocationSelector
   */
  public function getLocationSelector()
  {
    return $this->locationSelector;
  }
  /**
   * Optional. Roll out to resources by Cloud Resource Manager resource
   * hierarchy.
   *
   * @param RolloutPlanWaveSelectorResourceHierarchySelector $resourceHierarchySelector
   */
  public function setResourceHierarchySelector(RolloutPlanWaveSelectorResourceHierarchySelector $resourceHierarchySelector)
  {
    $this->resourceHierarchySelector = $resourceHierarchySelector;
  }
  /**
   * @return RolloutPlanWaveSelectorResourceHierarchySelector
   */
  public function getResourceHierarchySelector()
  {
    return $this->resourceHierarchySelector;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlanWaveSelector::class, 'Google_Service_Compute_RolloutPlanWaveSelector');
