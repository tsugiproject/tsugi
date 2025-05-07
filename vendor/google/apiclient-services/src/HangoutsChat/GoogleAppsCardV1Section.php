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

class GoogleAppsCardV1Section extends \Google\Collection
{
  protected $collection_key = 'widgets';
  protected $collapseControlType = GoogleAppsCardV1CollapseControl::class;
  protected $collapseControlDataType = '';
  /**
   * @var bool
   */
  public $collapsible;
  /**
   * @var string
   */
  public $header;
  /**
   * @var int
   */
  public $uncollapsibleWidgetsCount;
  protected $widgetsType = GoogleAppsCardV1Widget::class;
  protected $widgetsDataType = 'array';

  /**
   * @param GoogleAppsCardV1CollapseControl
   */
  public function setCollapseControl(GoogleAppsCardV1CollapseControl $collapseControl)
  {
    $this->collapseControl = $collapseControl;
  }
  /**
   * @return GoogleAppsCardV1CollapseControl
   */
  public function getCollapseControl()
  {
    return $this->collapseControl;
  }
  /**
   * @param bool
   */
  public function setCollapsible($collapsible)
  {
    $this->collapsible = $collapsible;
  }
  /**
   * @return bool
   */
  public function getCollapsible()
  {
    return $this->collapsible;
  }
  /**
   * @param string
   */
  public function setHeader($header)
  {
    $this->header = $header;
  }
  /**
   * @return string
   */
  public function getHeader()
  {
    return $this->header;
  }
  /**
   * @param int
   */
  public function setUncollapsibleWidgetsCount($uncollapsibleWidgetsCount)
  {
    $this->uncollapsibleWidgetsCount = $uncollapsibleWidgetsCount;
  }
  /**
   * @return int
   */
  public function getUncollapsibleWidgetsCount()
  {
    return $this->uncollapsibleWidgetsCount;
  }
  /**
   * @param GoogleAppsCardV1Widget[]
   */
  public function setWidgets($widgets)
  {
    $this->widgets = $widgets;
  }
  /**
   * @return GoogleAppsCardV1Widget[]
   */
  public function getWidgets()
  {
    return $this->widgets;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleAppsCardV1Section::class, 'Google_Service_HangoutsChat_GoogleAppsCardV1Section');
