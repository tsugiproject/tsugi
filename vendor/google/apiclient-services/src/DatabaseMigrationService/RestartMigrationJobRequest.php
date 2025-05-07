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

class RestartMigrationJobRequest extends \Google\Model
{
  protected $objectsFilterType = MigrationJobObjectsConfig::class;
  protected $objectsFilterDataType = '';
  /**
   * @var bool
   */
  public $restartFailedObjects;
  /**
   * @var bool
   */
  public $skipValidation;

  /**
   * @param MigrationJobObjectsConfig
   */
  public function setObjectsFilter(MigrationJobObjectsConfig $objectsFilter)
  {
    $this->objectsFilter = $objectsFilter;
  }
  /**
   * @return MigrationJobObjectsConfig
   */
  public function getObjectsFilter()
  {
    return $this->objectsFilter;
  }
  /**
   * @param bool
   */
  public function setRestartFailedObjects($restartFailedObjects)
  {
    $this->restartFailedObjects = $restartFailedObjects;
  }
  /**
   * @return bool
   */
  public function getRestartFailedObjects()
  {
    return $this->restartFailedObjects;
  }
  /**
   * @param bool
   */
  public function setSkipValidation($skipValidation)
  {
    $this->skipValidation = $skipValidation;
  }
  /**
   * @return bool
   */
  public function getSkipValidation()
  {
    return $this->skipValidation;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RestartMigrationJobRequest::class, 'Google_Service_DatabaseMigrationService_RestartMigrationJobRequest');
