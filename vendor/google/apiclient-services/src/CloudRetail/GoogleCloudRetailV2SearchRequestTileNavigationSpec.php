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

namespace Google\Service\CloudRetail;

class GoogleCloudRetailV2SearchRequestTileNavigationSpec extends \Google\Collection
{
  protected $collection_key = 'appliedTiles';
  protected $appliedTilesType = GoogleCloudRetailV2Tile::class;
  protected $appliedTilesDataType = 'array';
  /**
   * @var bool
   */
  public $tileNavigationRequested;

  /**
   * @param GoogleCloudRetailV2Tile[]
   */
  public function setAppliedTiles($appliedTiles)
  {
    $this->appliedTiles = $appliedTiles;
  }
  /**
   * @return GoogleCloudRetailV2Tile[]
   */
  public function getAppliedTiles()
  {
    return $this->appliedTiles;
  }
  /**
   * @param bool
   */
  public function setTileNavigationRequested($tileNavigationRequested)
  {
    $this->tileNavigationRequested = $tileNavigationRequested;
  }
  /**
   * @return bool
   */
  public function getTileNavigationRequested()
  {
    return $this->tileNavigationRequested;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRetailV2SearchRequestTileNavigationSpec::class, 'Google_Service_CloudRetail_GoogleCloudRetailV2SearchRequestTileNavigationSpec');
