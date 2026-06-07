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

class ToolName extends \Google\Model
{
  /**
   * Operation unspecified.
   */
  public const OPERATION_OPERATION_UNSPECIFIED = 'OPERATION_UNSPECIFIED';
  /**
   * LIST entities.
   */
  public const OPERATION_LIST = 'LIST';
  /**
   * GET entity.
   */
  public const OPERATION_GET = 'GET';
  /**
   * CREATE entity.
   */
  public const OPERATION_CREATE = 'CREATE';
  /**
   * UPDATE entity.
   */
  public const OPERATION_UPDATE = 'UPDATE';
  /**
   * DELETE entity.
   */
  public const OPERATION_DELETE = 'DELETE';
  /**
   * Entity name for which the tool was generated.
   *
   * @var string
   */
  public $entityName;
  /**
   * Tool name that was generated in the list tools call.
   *
   * @var string
   */
  public $name;
  /**
   * Operation for which the tool was generated.
   *
   * @var string
   */
  public $operation;

  /**
   * Entity name for which the tool was generated.
   *
   * @param string $entityName
   */
  public function setEntityName($entityName)
  {
    $this->entityName = $entityName;
  }
  /**
   * @return string
   */
  public function getEntityName()
  {
    return $this->entityName;
  }
  /**
   * Tool name that was generated in the list tools call.
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
   * Operation for which the tool was generated.
   *
   * Accepted values: OPERATION_UNSPECIFIED, LIST, GET, CREATE, UPDATE, DELETE
   *
   * @param self::OPERATION_* $operation
   */
  public function setOperation($operation)
  {
    $this->operation = $operation;
  }
  /**
   * @return self::OPERATION_*
   */
  public function getOperation()
  {
    return $this->operation;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ToolName::class, 'Google_Service_Connectors_ToolName');
