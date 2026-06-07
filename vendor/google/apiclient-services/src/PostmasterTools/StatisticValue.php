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

namespace Google\Service\PostmasterTools;

class StatisticValue extends \Google\Model
{
  /**
   * Double value.
   *
   * @var 
   */
  public $doubleValue;
  /**
   * Float value.
   *
   * @var float
   */
  public $floatValue;
  /**
   * Integer value.
   *
   * @var string
   */
  public $intValue;
  protected $stringListType = StringList::class;
  protected $stringListDataType = '';
  /**
   * String value.
   *
   * @var string
   */
  public $stringValue;

  public function setDoubleValue($doubleValue)
  {
    $this->doubleValue = $doubleValue;
  }
  public function getDoubleValue()
  {
    return $this->doubleValue;
  }
  /**
   * Float value.
   *
   * @param float $floatValue
   */
  public function setFloatValue($floatValue)
  {
    $this->floatValue = $floatValue;
  }
  /**
   * @return float
   */
  public function getFloatValue()
  {
    return $this->floatValue;
  }
  /**
   * Integer value.
   *
   * @param string $intValue
   */
  public function setIntValue($intValue)
  {
    $this->intValue = $intValue;
  }
  /**
   * @return string
   */
  public function getIntValue()
  {
    return $this->intValue;
  }
  /**
   * List of string values.
   *
   * @param StringList $stringList
   */
  public function setStringList(StringList $stringList)
  {
    $this->stringList = $stringList;
  }
  /**
   * @return StringList
   */
  public function getStringList()
  {
    return $this->stringList;
  }
  /**
   * String value.
   *
   * @param string $stringValue
   */
  public function setStringValue($stringValue)
  {
    $this->stringValue = $stringValue;
  }
  /**
   * @return string
   */
  public function getStringValue()
  {
    return $this->stringValue;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(StatisticValue::class, 'Google_Service_PostmasterTools_StatisticValue');
