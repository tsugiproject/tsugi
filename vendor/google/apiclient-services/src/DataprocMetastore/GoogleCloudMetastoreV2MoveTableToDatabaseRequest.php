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

namespace Google\Service\DataprocMetastore;

class GoogleCloudMetastoreV2MoveTableToDatabaseRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $dbName;
  /**
   * @var string
   */
  public $destinationDbName;
  /**
   * @var string
   */
  public $tableName;

  /**
   * @param string
   */
  public function setDbName($dbName)
  {
    $this->dbName = $dbName;
  }
  /**
   * @return string
   */
  public function getDbName()
  {
    return $this->dbName;
  }
  /**
   * @param string
   */
  public function setDestinationDbName($destinationDbName)
  {
    $this->destinationDbName = $destinationDbName;
  }
  /**
   * @return string
   */
  public function getDestinationDbName()
  {
    return $this->destinationDbName;
  }
  /**
   * @param string
   */
  public function setTableName($tableName)
  {
    $this->tableName = $tableName;
  }
  /**
   * @return string
   */
  public function getTableName()
  {
    return $this->tableName;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudMetastoreV2MoveTableToDatabaseRequest::class, 'Google_Service_DataprocMetastore_GoogleCloudMetastoreV2MoveTableToDatabaseRequest');
