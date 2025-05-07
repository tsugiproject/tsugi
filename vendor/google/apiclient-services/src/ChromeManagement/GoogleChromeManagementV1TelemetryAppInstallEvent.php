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

namespace Google\Service\ChromeManagement;

class GoogleChromeManagementV1TelemetryAppInstallEvent extends \Google\Model
{
  /**
   * @var string
   */
  public $appId;
  /**
   * @var string
   */
  public $appInstallReason;
  /**
   * @var string
   */
  public $appInstallSource;
  /**
   * @var string
   */
  public $appInstallTime;
  /**
   * @var string
   */
  public $appType;

  /**
   * @param string
   */
  public function setAppId($appId)
  {
    $this->appId = $appId;
  }
  /**
   * @return string
   */
  public function getAppId()
  {
    return $this->appId;
  }
  /**
   * @param string
   */
  public function setAppInstallReason($appInstallReason)
  {
    $this->appInstallReason = $appInstallReason;
  }
  /**
   * @return string
   */
  public function getAppInstallReason()
  {
    return $this->appInstallReason;
  }
  /**
   * @param string
   */
  public function setAppInstallSource($appInstallSource)
  {
    $this->appInstallSource = $appInstallSource;
  }
  /**
   * @return string
   */
  public function getAppInstallSource()
  {
    return $this->appInstallSource;
  }
  /**
   * @param string
   */
  public function setAppInstallTime($appInstallTime)
  {
    $this->appInstallTime = $appInstallTime;
  }
  /**
   * @return string
   */
  public function getAppInstallTime()
  {
    return $this->appInstallTime;
  }
  /**
   * @param string
   */
  public function setAppType($appType)
  {
    $this->appType = $appType;
  }
  /**
   * @return string
   */
  public function getAppType()
  {
    return $this->appType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleChromeManagementV1TelemetryAppInstallEvent::class, 'Google_Service_ChromeManagement_GoogleChromeManagementV1TelemetryAppInstallEvent');
