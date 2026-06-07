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

namespace Google\Service\Reports;

class ActivityEventsSensitiveParameters extends \Google\Collection
{
  protected $collection_key = 'multiValue';
  /**
   * Boolean value of the parameter.
   *
   * @var bool
   */
  public $boolValue;
  /**
   * Integer value of the parameter.
   *
   * @var string
   */
  public $intValue;
  protected $messageValueType = ActivityEventsSensitiveParametersMessageValue::class;
  protected $messageValueDataType = '';
  /**
   * Integer values of the parameter.
   *
   * @var string[]
   */
  public $multiIntValue;
  protected $multiMessageValueType = ActivityEventsSensitiveParametersMultiMessageValue::class;
  protected $multiMessageValueDataType = 'array';
  /**
   * String values of the parameter.
   *
   * @var string[]
   */
  public $multiValue;
  /**
   * The name of the parameter.
   *
   * @var string
   */
  public $name;
  /**
   * String value of the parameter.
   *
   * @var string
   */
  public $value;

  /**
   * Boolean value of the parameter.
   *
   * @param bool $boolValue
   */
  public function setBoolValue($boolValue)
  {
    $this->boolValue = $boolValue;
  }
  /**
   * @return bool
   */
  public function getBoolValue()
  {
    return $this->boolValue;
  }
  /**
   * Integer value of the parameter.
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
   * Nested parameter value pairs associated with this parameter. Complex value
   * type for a parameter are returned as a list of parameter values. For
   * example, the address parameter may have a value as `[{parameter: [{name:
   * city, value: abc}]}]`
   *
   * @param ActivityEventsSensitiveParametersMessageValue $messageValue
   */
  public function setMessageValue(ActivityEventsSensitiveParametersMessageValue $messageValue)
  {
    $this->messageValue = $messageValue;
  }
  /**
   * @return ActivityEventsSensitiveParametersMessageValue
   */
  public function getMessageValue()
  {
    return $this->messageValue;
  }
  /**
   * Integer values of the parameter.
   *
   * @param string[] $multiIntValue
   */
  public function setMultiIntValue($multiIntValue)
  {
    $this->multiIntValue = $multiIntValue;
  }
  /**
   * @return string[]
   */
  public function getMultiIntValue()
  {
    return $this->multiIntValue;
  }
  /**
   * List of `messageValue` objects.
   *
   * @param ActivityEventsSensitiveParametersMultiMessageValue[] $multiMessageValue
   */
  public function setMultiMessageValue($multiMessageValue)
  {
    $this->multiMessageValue = $multiMessageValue;
  }
  /**
   * @return ActivityEventsSensitiveParametersMultiMessageValue[]
   */
  public function getMultiMessageValue()
  {
    return $this->multiMessageValue;
  }
  /**
   * String values of the parameter.
   *
   * @param string[] $multiValue
   */
  public function setMultiValue($multiValue)
  {
    $this->multiValue = $multiValue;
  }
  /**
   * @return string[]
   */
  public function getMultiValue()
  {
    return $this->multiValue;
  }
  /**
   * The name of the parameter.
   *
   * @param string $name
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
   * String value of the parameter.
   *
   * @param string $value
   */
  public function setValue($value)
  {
    $this->value = $value;
  }
  /**
   * @return string
   */
  public function getValue()
  {
    return $this->value;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ActivityEventsSensitiveParameters::class, 'Google_Service_Reports_ActivityEventsSensitiveParameters');
