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

namespace Google\Service\VersionHistory;

class Release extends \Google\Model
{
  public $fraction;
  /**
   * @var string
   */
  public $fractionGroup;
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $pinnable;
  protected $servingType = Interval::class;
  protected $servingDataType = '';
  /**
   * @var string
   */
  public $version;

  public function setFraction($fraction)
  {
    $this->fraction = $fraction;
  }
  public function getFraction()
  {
    return $this->fraction;
  }
  /**
   * @param string
   */
  public function setFractionGroup($fractionGroup)
  {
    $this->fractionGroup = $fractionGroup;
  }
  /**
   * @return string
   */
  public function getFractionGroup()
  {
    return $this->fractionGroup;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param bool
   */
  public function setPinnable($pinnable)
  {
    $this->pinnable = $pinnable;
  }
  /**
   * @return bool
   */
  public function getPinnable()
  {
    return $this->pinnable;
  }
  /**
   * @param Interval
   */
  public function setServing(Interval $serving)
  {
    $this->serving = $serving;
  }
  /**
   * @return Interval
   */
  public function getServing()
  {
    return $this->serving;
  }
  /**
   * @param string
   */
  public function setVersion($version)
  {
    $this->version = $version;
  }
  /**
   * @return string
   */
  public function getVersion()
  {
    return $this->version;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Release::class, 'Google_Service_VersionHistory_Release');
