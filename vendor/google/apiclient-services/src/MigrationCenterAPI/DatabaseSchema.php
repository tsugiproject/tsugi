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

namespace Google\Service\MigrationCenterAPI;

class DatabaseSchema extends \Google\Collection
{
  protected $collection_key = 'objects';
  protected $mysqlType = MySqlSchemaDetails::class;
  protected $mysqlDataType = '';
  protected $objectsType = DatabaseObjects::class;
  protected $objectsDataType = 'array';
  protected $postgresqlType = PostgreSqlSchemaDetails::class;
  protected $postgresqlDataType = '';
  /**
   * @var string
   */
  public $schemaName;
  protected $sqlServerType = SqlServerSchemaDetails::class;
  protected $sqlServerDataType = '';
  /**
   * @var string
   */
  public $tablesSizeBytes;

  /**
   * @param MySqlSchemaDetails
   */
  public function setMysql(MySqlSchemaDetails $mysql)
  {
    $this->mysql = $mysql;
  }
  /**
   * @return MySqlSchemaDetails
   */
  public function getMysql()
  {
    return $this->mysql;
  }
  /**
   * @param DatabaseObjects[]
   */
  public function setObjects($objects)
  {
    $this->objects = $objects;
  }
  /**
   * @return DatabaseObjects[]
   */
  public function getObjects()
  {
    return $this->objects;
  }
  /**
   * @param PostgreSqlSchemaDetails
   */
  public function setPostgresql(PostgreSqlSchemaDetails $postgresql)
  {
    $this->postgresql = $postgresql;
  }
  /**
   * @return PostgreSqlSchemaDetails
   */
  public function getPostgresql()
  {
    return $this->postgresql;
  }
  /**
   * @param string
   */
  public function setSchemaName($schemaName)
  {
    $this->schemaName = $schemaName;
  }
  /**
   * @return string
   */
  public function getSchemaName()
  {
    return $this->schemaName;
  }
  /**
   * @param SqlServerSchemaDetails
   */
  public function setSqlServer(SqlServerSchemaDetails $sqlServer)
  {
    $this->sqlServer = $sqlServer;
  }
  /**
   * @return SqlServerSchemaDetails
   */
  public function getSqlServer()
  {
    return $this->sqlServer;
  }
  /**
   * @param string
   */
  public function setTablesSizeBytes($tablesSizeBytes)
  {
    $this->tablesSizeBytes = $tablesSizeBytes;
  }
  /**
   * @return string
   */
  public function getTablesSizeBytes()
  {
    return $this->tablesSizeBytes;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DatabaseSchema::class, 'Google_Service_MigrationCenterAPI_DatabaseSchema');
