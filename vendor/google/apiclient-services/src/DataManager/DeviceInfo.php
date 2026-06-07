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

class DeviceInfo extends \Google\Model
{
  /**
   * Optional. The brand of the device.
   *
   * @var string
   */
  public $brand;
  /**
   * Optional. The brand or type of the browser.
   *
   * @var string
   */
  public $browser;
  /**
   * Optional. The version of the browser.
   *
   * @var string
   */
  public $browserVersion;
  /**
   * Optional. The category of device. For example, “desktop”, “tablet”,
   * “mobile”, “smart TV”.
   *
   * @var string
   */
  public $category;
  /**
   * Optional. The IP address of the device for the given context. **Note:**
   * Google Ads does not support IP address matching for end users in the
   * European Economic Area (EEA), United Kingdom (UK), or Switzerland (CH). Add
   * logic to conditionally exclude sharing IP addresses from users from these
   * regions and ensure that you provide users with clear and comprehensive
   * information about the data you collect on your sites, apps, and other
   * properties and get consent where required by law or any applicable Google
   * policies. See the [About offline conversion
   * imports](https://support.google.com/google-ads/answer/2998031) page for
   * more details.
   *
   * @var string
   */
  public $ipAddress;
  /**
   * Optional. The language the device uses in ISO 639-1 format.
   *
   * @var string
   */
  public $languageCode;
  /**
   * Optional. The model of the device.
   *
   * @var string
   */
  public $model;
  /**
   * Optional. The operating system or platform of the device.
   *
   * @var string
   */
  public $operatingSystem;
  /**
   * Optional. The version of the operating system or platform.
   *
   * @var string
   */
  public $operatingSystemVersion;
  /**
   * Optional. The height of the screen in pixels.
   *
   * @var int
   */
  public $screenHeight;
  /**
   * Optional. The width of the screen in pixels.
   *
   * @var int
   */
  public $screenWidth;
  /**
   * Optional. The user-agent string of the device for the given context.
   *
   * @var string
   */
  public $userAgent;

  /**
   * Optional. The brand of the device.
   *
   * @param string $brand
   */
  public function setBrand($brand)
  {
    $this->brand = $brand;
  }
  /**
   * @return string
   */
  public function getBrand()
  {
    return $this->brand;
  }
  /**
   * Optional. The brand or type of the browser.
   *
   * @param string $browser
   */
  public function setBrowser($browser)
  {
    $this->browser = $browser;
  }
  /**
   * @return string
   */
  public function getBrowser()
  {
    return $this->browser;
  }
  /**
   * Optional. The version of the browser.
   *
   * @param string $browserVersion
   */
  public function setBrowserVersion($browserVersion)
  {
    $this->browserVersion = $browserVersion;
  }
  /**
   * @return string
   */
  public function getBrowserVersion()
  {
    return $this->browserVersion;
  }
  /**
   * Optional. The category of device. For example, “desktop”, “tablet”,
   * “mobile”, “smart TV”.
   *
   * @param string $category
   */
  public function setCategory($category)
  {
    $this->category = $category;
  }
  /**
   * @return string
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * Optional. The IP address of the device for the given context. **Note:**
   * Google Ads does not support IP address matching for end users in the
   * European Economic Area (EEA), United Kingdom (UK), or Switzerland (CH). Add
   * logic to conditionally exclude sharing IP addresses from users from these
   * regions and ensure that you provide users with clear and comprehensive
   * information about the data you collect on your sites, apps, and other
   * properties and get consent where required by law or any applicable Google
   * policies. See the [About offline conversion
   * imports](https://support.google.com/google-ads/answer/2998031) page for
   * more details.
   *
   * @param string $ipAddress
   */
  public function setIpAddress($ipAddress)
  {
    $this->ipAddress = $ipAddress;
  }
  /**
   * @return string
   */
  public function getIpAddress()
  {
    return $this->ipAddress;
  }
  /**
   * Optional. The language the device uses in ISO 639-1 format.
   *
   * @param string $languageCode
   */
  public function setLanguageCode($languageCode)
  {
    $this->languageCode = $languageCode;
  }
  /**
   * @return string
   */
  public function getLanguageCode()
  {
    return $this->languageCode;
  }
  /**
   * Optional. The model of the device.
   *
   * @param string $model
   */
  public function setModel($model)
  {
    $this->model = $model;
  }
  /**
   * @return string
   */
  public function getModel()
  {
    return $this->model;
  }
  /**
   * Optional. The operating system or platform of the device.
   *
   * @param string $operatingSystem
   */
  public function setOperatingSystem($operatingSystem)
  {
    $this->operatingSystem = $operatingSystem;
  }
  /**
   * @return string
   */
  public function getOperatingSystem()
  {
    return $this->operatingSystem;
  }
  /**
   * Optional. The version of the operating system or platform.
   *
   * @param string $operatingSystemVersion
   */
  public function setOperatingSystemVersion($operatingSystemVersion)
  {
    $this->operatingSystemVersion = $operatingSystemVersion;
  }
  /**
   * @return string
   */
  public function getOperatingSystemVersion()
  {
    return $this->operatingSystemVersion;
  }
  /**
   * Optional. The height of the screen in pixels.
   *
   * @param int $screenHeight
   */
  public function setScreenHeight($screenHeight)
  {
    $this->screenHeight = $screenHeight;
  }
  /**
   * @return int
   */
  public function getScreenHeight()
  {
    return $this->screenHeight;
  }
  /**
   * Optional. The width of the screen in pixels.
   *
   * @param int $screenWidth
   */
  public function setScreenWidth($screenWidth)
  {
    $this->screenWidth = $screenWidth;
  }
  /**
   * @return int
   */
  public function getScreenWidth()
  {
    return $this->screenWidth;
  }
  /**
   * Optional. The user-agent string of the device for the given context.
   *
   * @param string $userAgent
   */
  public function setUserAgent($userAgent)
  {
    $this->userAgent = $userAgent;
  }
  /**
   * @return string
   */
  public function getUserAgent()
  {
    return $this->userAgent;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeviceInfo::class, 'Google_Service_DataManager_DeviceInfo');
