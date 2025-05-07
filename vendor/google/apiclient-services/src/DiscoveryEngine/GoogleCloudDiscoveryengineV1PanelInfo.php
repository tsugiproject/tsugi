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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1PanelInfo extends \Google\Collection
{
  protected $collection_key = 'documents';
  /**
   * @var string
   */
  public $displayName;
  protected $documentsType = GoogleCloudDiscoveryengineV1DocumentInfo::class;
  protected $documentsDataType = 'array';
  /**
   * @var string
   */
  public $panelId;
  /**
   * @var int
   */
  public $panelPosition;
  /**
   * @var int
   */
  public $totalPanels;

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
   * @param GoogleCloudDiscoveryengineV1DocumentInfo[]
   */
  public function setDocuments($documents)
  {
    $this->documents = $documents;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1DocumentInfo[]
   */
  public function getDocuments()
  {
    return $this->documents;
  }
  /**
   * @param string
   */
  public function setPanelId($panelId)
  {
    $this->panelId = $panelId;
  }
  /**
   * @return string
   */
  public function getPanelId()
  {
    return $this->panelId;
  }
  /**
   * @param int
   */
  public function setPanelPosition($panelPosition)
  {
    $this->panelPosition = $panelPosition;
  }
  /**
   * @return int
   */
  public function getPanelPosition()
  {
    return $this->panelPosition;
  }
  /**
   * @param int
   */
  public function setTotalPanels($totalPanels)
  {
    $this->totalPanels = $totalPanels;
  }
  /**
   * @return int
   */
  public function getTotalPanels()
  {
    return $this->totalPanels;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1PanelInfo::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1PanelInfo');
