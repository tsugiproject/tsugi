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

namespace Google\Service\DisplayVideo;

class AdGroupInventoryControl extends \Google\Model
{
  /**
   * Not specified or unknown.
   */
  public const AD_GROUP_INVENTORY_STRATEGY_AD_GROUP_INVENTORY_STRATEGY_UNSPECIFIED = 'AD_GROUP_INVENTORY_STRATEGY_UNSPECIFIED';
  /**
   * The ad group is opted-in to all Google and Display Network inventory.
   */
  public const AD_GROUP_INVENTORY_STRATEGY_AD_GROUP_INVENTORY_STRATEGY_ALL_GOOGLE_AND_DISPLAY_NETWORK_INVENTORY = 'AD_GROUP_INVENTORY_STRATEGY_ALL_GOOGLE_AND_DISPLAY_NETWORK_INVENTORY';
  /**
   * The ad group is opted-in to all Google inventory.
   */
  public const AD_GROUP_INVENTORY_STRATEGY_AD_GROUP_INVENTORY_STRATEGY_ALL_GOOGLE_INVENTORY = 'AD_GROUP_INVENTORY_STRATEGY_ALL_GOOGLE_INVENTORY';
  /**
   * The inventory strategy.
   *
   * @var string
   */
  public $adGroupInventoryStrategy;
  protected $selectedInventoriesType = SelectedInventories::class;
  protected $selectedInventoriesDataType = '';

  /**
   * The inventory strategy.
   *
   * Accepted values: AD_GROUP_INVENTORY_STRATEGY_UNSPECIFIED,
   * AD_GROUP_INVENTORY_STRATEGY_ALL_GOOGLE_AND_DISPLAY_NETWORK_INVENTORY,
   * AD_GROUP_INVENTORY_STRATEGY_ALL_GOOGLE_INVENTORY
   *
   * @param self::AD_GROUP_INVENTORY_STRATEGY_* $adGroupInventoryStrategy
   */
  public function setAdGroupInventoryStrategy($adGroupInventoryStrategy)
  {
    $this->adGroupInventoryStrategy = $adGroupInventoryStrategy;
  }
  /**
   * @return self::AD_GROUP_INVENTORY_STRATEGY_*
   */
  public function getAdGroupInventoryStrategy()
  {
    return $this->adGroupInventoryStrategy;
  }
  /**
   * The selected inventories.
   *
   * @param SelectedInventories $selectedInventories
   */
  public function setSelectedInventories(SelectedInventories $selectedInventories)
  {
    $this->selectedInventories = $selectedInventories;
  }
  /**
   * @return SelectedInventories
   */
  public function getSelectedInventories()
  {
    return $this->selectedInventories;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AdGroupInventoryControl::class, 'Google_Service_DisplayVideo_AdGroupInventoryControl');
