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

namespace Google\Service\Connectors;

class ResultMetadata extends \Google\Model
{
  /**
   * @var string
   */
  public $dataType;
  /**
   * @var array
   */
  public $defaultValue;
  /**
   * @var string
   */
  public $description;
  protected $jsonSchemaType = JsonSchema::class;
  protected $jsonSchemaDataType = '';
  /**
   * @var string
   */
  public $name;
  /**
   * @var bool
   */
  public $nullable;

  /**
   * @param string
   */
  public function setDataType($dataType)
  {
    $this->dataType = $dataType;
  }
  /**
   * @return string
   */
  public function getDataType()
  {
    return $this->dataType;
  }
  /**
   * @param array
   */
  public function setDefaultValue($defaultValue)
  {
    $this->defaultValue = $defaultValue;
  }
  /**
   * @return array
   */
  public function getDefaultValue()
  {
    return $this->defaultValue;
  }
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
   * @param JsonSchema
   */
  public function setJsonSchema(JsonSchema $jsonSchema)
  {
    $this->jsonSchema = $jsonSchema;
  }
  /**
   * @return JsonSchema
   */
  public function getJsonSchema()
  {
    return $this->jsonSchema;
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
  public function setNullable($nullable)
  {
    $this->nullable = $nullable;
  }
  /**
   * @return bool
   */
  public function getNullable()
  {
    return $this->nullable;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ResultMetadata::class, 'Google_Service_Connectors_ResultMetadata');
