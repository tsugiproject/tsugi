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

namespace Google\Service\CloudBuild;

class TaskResult extends \Google\Model
{
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $name;
  protected $propertiesType = PropertySpec::class;
  protected $propertiesDataType = 'map';
  /**
   * @var string
   */
  public $type;
  protected $valueType = ParamValue::class;
  protected $valueDataType = '';

  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
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
   * @param PropertySpec[]
   */
  public function setProperties($properties)
  {
    $this->properties = $properties;
  }
  /**
   * @return PropertySpec[]
   */
  public function getProperties()
  {
    return $this->properties;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
  /**
   * @param ParamValue
   */
  public function setValue(ParamValue $value)
  {
    $this->value = $value;
  }
  /**
   * @return ParamValue
   */
  public function getValue()
  {
    return $this->value;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TaskResult::class, 'Google_Service_CloudBuild_TaskResult');
