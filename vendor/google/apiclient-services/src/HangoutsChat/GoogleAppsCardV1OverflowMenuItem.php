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

namespace Google\Service\HangoutsChat;

class GoogleAppsCardV1OverflowMenuItem extends \Google\Model
{
  /**
   * @var bool
   */
  public $disabled;
  protected $onClickType = GoogleAppsCardV1OnClick::class;
  protected $onClickDataType = '';
  protected $startIconType = GoogleAppsCardV1Icon::class;
  protected $startIconDataType = '';
  /**
   * @var string
   */
  public $text;

  /**
   * @param bool
   */
  public function setDisabled($disabled)
  {
    $this->disabled = $disabled;
  }
  /**
   * @return bool
   */
  public function getDisabled()
  {
    return $this->disabled;
  }
  /**
   * @param GoogleAppsCardV1OnClick
   */
  public function setOnClick(GoogleAppsCardV1OnClick $onClick)
  {
    $this->onClick = $onClick;
  }
  /**
   * @return GoogleAppsCardV1OnClick
   */
  public function getOnClick()
  {
    return $this->onClick;
  }
  /**
   * @param GoogleAppsCardV1Icon
   */
  public function setStartIcon(GoogleAppsCardV1Icon $startIcon)
  {
    $this->startIcon = $startIcon;
  }
  /**
   * @return GoogleAppsCardV1Icon
   */
  public function getStartIcon()
  {
    return $this->startIcon;
  }
  /**
   * @param string
   */
  public function setText($text)
  {
    $this->text = $text;
  }
  /**
   * @return string
   */
  public function getText()
  {
    return $this->text;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleAppsCardV1OverflowMenuItem::class, 'Google_Service_HangoutsChat_GoogleAppsCardV1OverflowMenuItem');
