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

namespace Google\Service\DataManager;

class EventLocation extends \Google\Model
{
  /**
   * Optional. The name of the city where the event occurred.
   *
   * @var string
   */
  public $city;
  /**
   * Optional. The continent code in UN M49 format where the event occurred.
   *
   * @var string
   */
  public $continentCode;
  /**
   * Optional. The 2-letter CLDR region code of the user's address.
   *
   * @var string
   */
  public $regionCode;
  /**
   * Optional. Required for Store Sales. The identifier to represent a physical
   * store where the event happened.
   *
   * @var string
   */
  public $storeId;
  /**
   * Optional. The subcontinent code in UN M49 format where the event occurred.
   *
   * @var string
   */
  public $subcontinentCode;
  /**
   * Optional. The ISO 3166-2 subdivision code where the event occurred.
   *
   * @var string
   */
  public $subdivisionCode;

  /**
   * Optional. The name of the city where the event occurred.
   *
   * @param string $city
   */
  public function setCity($city)
  {
    $this->city = $city;
  }
  /**
   * @return string
   */
  public function getCity()
  {
    return $this->city;
  }
  /**
   * Optional. The continent code in UN M49 format where the event occurred.
   *
   * @param string $continentCode
   */
  public function setContinentCode($continentCode)
  {
    $this->continentCode = $continentCode;
  }
  /**
   * @return string
   */
  public function getContinentCode()
  {
    return $this->continentCode;
  }
  /**
   * Optional. The 2-letter CLDR region code of the user's address.
   *
   * @param string $regionCode
   */
  public function setRegionCode($regionCode)
  {
    $this->regionCode = $regionCode;
  }
  /**
   * @return string
   */
  public function getRegionCode()
  {
    return $this->regionCode;
  }
  /**
   * Optional. Required for Store Sales. The identifier to represent a physical
   * store where the event happened.
   *
   * @param string $storeId
   */
  public function setStoreId($storeId)
  {
    $this->storeId = $storeId;
  }
  /**
   * @return string
   */
  public function getStoreId()
  {
    return $this->storeId;
  }
  /**
   * Optional. The subcontinent code in UN M49 format where the event occurred.
   *
   * @param string $subcontinentCode
   */
  public function setSubcontinentCode($subcontinentCode)
  {
    $this->subcontinentCode = $subcontinentCode;
  }
  /**
   * @return string
   */
  public function getSubcontinentCode()
  {
    return $this->subcontinentCode;
  }
  /**
   * Optional. The ISO 3166-2 subdivision code where the event occurred.
   *
   * @param string $subdivisionCode
   */
  public function setSubdivisionCode($subdivisionCode)
  {
    $this->subdivisionCode = $subdivisionCode;
  }
  /**
   * @return string
   */
  public function getSubdivisionCode()
  {
    return $this->subdivisionCode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EventLocation::class, 'Google_Service_DataManager_EventLocation');
