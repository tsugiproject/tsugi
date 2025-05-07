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

namespace Google\Service\Walletobjects;

class AppLinkData extends \Google\Model
{
  protected $androidAppLinkInfoType = AppLinkDataAppLinkInfo::class;
  protected $androidAppLinkInfoDataType = '';
  protected $displayTextType = LocalizedString::class;
  protected $displayTextDataType = '';
  protected $iosAppLinkInfoType = AppLinkDataAppLinkInfo::class;
  protected $iosAppLinkInfoDataType = '';
  protected $webAppLinkInfoType = AppLinkDataAppLinkInfo::class;
  protected $webAppLinkInfoDataType = '';

  /**
   * @param AppLinkDataAppLinkInfo
   */
  public function setAndroidAppLinkInfo(AppLinkDataAppLinkInfo $androidAppLinkInfo)
  {
    $this->androidAppLinkInfo = $androidAppLinkInfo;
  }
  /**
   * @return AppLinkDataAppLinkInfo
   */
  public function getAndroidAppLinkInfo()
  {
    return $this->androidAppLinkInfo;
  }
  /**
   * @param LocalizedString
   */
  public function setDisplayText(LocalizedString $displayText)
  {
    $this->displayText = $displayText;
  }
  /**
   * @return LocalizedString
   */
  public function getDisplayText()
  {
    return $this->displayText;
  }
  /**
   * @param AppLinkDataAppLinkInfo
   */
  public function setIosAppLinkInfo(AppLinkDataAppLinkInfo $iosAppLinkInfo)
  {
    $this->iosAppLinkInfo = $iosAppLinkInfo;
  }
  /**
   * @return AppLinkDataAppLinkInfo
   */
  public function getIosAppLinkInfo()
  {
    return $this->iosAppLinkInfo;
  }
  /**
   * @param AppLinkDataAppLinkInfo
   */
  public function setWebAppLinkInfo(AppLinkDataAppLinkInfo $webAppLinkInfo)
  {
    $this->webAppLinkInfo = $webAppLinkInfo;
  }
  /**
   * @return AppLinkDataAppLinkInfo
   */
  public function getWebAppLinkInfo()
  {
    return $this->webAppLinkInfo;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AppLinkData::class, 'Google_Service_Walletobjects_AppLinkData');
