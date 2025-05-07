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

namespace Google\Service\DLP;

class GooglePrivacyDlpV2DataProfileJobConfig extends \Google\Collection
{
  protected $collection_key = 'inspectTemplates';
  protected $dataProfileActionsType = GooglePrivacyDlpV2DataProfileAction::class;
  protected $dataProfileActionsDataType = 'array';
  /**
   * @var string[]
   */
  public $inspectTemplates;
  protected $locationType = GooglePrivacyDlpV2DataProfileLocation::class;
  protected $locationDataType = '';
  protected $otherCloudStartingLocationType = GooglePrivacyDlpV2OtherCloudDiscoveryStartingLocation::class;
  protected $otherCloudStartingLocationDataType = '';
  /**
   * @var string
   */
  public $projectId;

  /**
   * @param GooglePrivacyDlpV2DataProfileAction[]
   */
  public function setDataProfileActions($dataProfileActions)
  {
    $this->dataProfileActions = $dataProfileActions;
  }
  /**
   * @return GooglePrivacyDlpV2DataProfileAction[]
   */
  public function getDataProfileActions()
  {
    return $this->dataProfileActions;
  }
  /**
   * @param string[]
   */
  public function setInspectTemplates($inspectTemplates)
  {
    $this->inspectTemplates = $inspectTemplates;
  }
  /**
   * @return string[]
   */
  public function getInspectTemplates()
  {
    return $this->inspectTemplates;
  }
  /**
   * @param GooglePrivacyDlpV2DataProfileLocation
   */
  public function setLocation(GooglePrivacyDlpV2DataProfileLocation $location)
  {
    $this->location = $location;
  }
  /**
   * @return GooglePrivacyDlpV2DataProfileLocation
   */
  public function getLocation()
  {
    return $this->location;
  }
  /**
   * @param GooglePrivacyDlpV2OtherCloudDiscoveryStartingLocation
   */
  public function setOtherCloudStartingLocation(GooglePrivacyDlpV2OtherCloudDiscoveryStartingLocation $otherCloudStartingLocation)
  {
    $this->otherCloudStartingLocation = $otherCloudStartingLocation;
  }
  /**
   * @return GooglePrivacyDlpV2OtherCloudDiscoveryStartingLocation
   */
  public function getOtherCloudStartingLocation()
  {
    return $this->otherCloudStartingLocation;
  }
  /**
   * @param string
   */
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  /**
   * @return string
   */
  public function getProjectId()
  {
    return $this->projectId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2DataProfileJobConfig::class, 'Google_Service_DLP_GooglePrivacyDlpV2DataProfileJobConfig');
