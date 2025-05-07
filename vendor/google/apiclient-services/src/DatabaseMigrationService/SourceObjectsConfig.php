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

namespace Google\Service\DatabaseMigrationService;

class SourceObjectsConfig extends \Google\Collection
{
  protected $collection_key = 'objectConfigs';
  protected $objectConfigsType = SourceObjectConfig::class;
  protected $objectConfigsDataType = 'array';
  /**
   * @var string
   */
  public $objectsSelectionType;

  /**
   * @param SourceObjectConfig[]
   */
  public function setObjectConfigs($objectConfigs)
  {
    $this->objectConfigs = $objectConfigs;
  }
  /**
   * @return SourceObjectConfig[]
   */
  public function getObjectConfigs()
  {
    return $this->objectConfigs;
  }
  /**
   * @param string
   */
  public function setObjectsSelectionType($objectsSelectionType)
  {
    $this->objectsSelectionType = $objectsSelectionType;
  }
  /**
   * @return string
   */
  public function getObjectsSelectionType()
  {
    return $this->objectsSelectionType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SourceObjectsConfig::class, 'Google_Service_DatabaseMigrationService_SourceObjectsConfig');
