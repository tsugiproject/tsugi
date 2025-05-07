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

namespace Google\Service\Datastream;

class BackfillAllStrategy extends \Google\Model
{
  protected $mysqlExcludedObjectsType = MysqlRdbms::class;
  protected $mysqlExcludedObjectsDataType = '';
  protected $oracleExcludedObjectsType = OracleRdbms::class;
  protected $oracleExcludedObjectsDataType = '';
  protected $postgresqlExcludedObjectsType = PostgresqlRdbms::class;
  protected $postgresqlExcludedObjectsDataType = '';
  protected $salesforceExcludedObjectsType = SalesforceOrg::class;
  protected $salesforceExcludedObjectsDataType = '';
  protected $sqlServerExcludedObjectsType = SqlServerRdbms::class;
  protected $sqlServerExcludedObjectsDataType = '';

  /**
   * @param MysqlRdbms
   */
  public function setMysqlExcludedObjects(MysqlRdbms $mysqlExcludedObjects)
  {
    $this->mysqlExcludedObjects = $mysqlExcludedObjects;
  }
  /**
   * @return MysqlRdbms
   */
  public function getMysqlExcludedObjects()
  {
    return $this->mysqlExcludedObjects;
  }
  /**
   * @param OracleRdbms
   */
  public function setOracleExcludedObjects(OracleRdbms $oracleExcludedObjects)
  {
    $this->oracleExcludedObjects = $oracleExcludedObjects;
  }
  /**
   * @return OracleRdbms
   */
  public function getOracleExcludedObjects()
  {
    return $this->oracleExcludedObjects;
  }
  /**
   * @param PostgresqlRdbms
   */
  public function setPostgresqlExcludedObjects(PostgresqlRdbms $postgresqlExcludedObjects)
  {
    $this->postgresqlExcludedObjects = $postgresqlExcludedObjects;
  }
  /**
   * @return PostgresqlRdbms
   */
  public function getPostgresqlExcludedObjects()
  {
    return $this->postgresqlExcludedObjects;
  }
  /**
   * @param SalesforceOrg
   */
  public function setSalesforceExcludedObjects(SalesforceOrg $salesforceExcludedObjects)
  {
    $this->salesforceExcludedObjects = $salesforceExcludedObjects;
  }
  /**
   * @return SalesforceOrg
   */
  public function getSalesforceExcludedObjects()
  {
    return $this->salesforceExcludedObjects;
  }
  /**
   * @param SqlServerRdbms
   */
  public function setSqlServerExcludedObjects(SqlServerRdbms $sqlServerExcludedObjects)
  {
    $this->sqlServerExcludedObjects = $sqlServerExcludedObjects;
  }
  /**
   * @return SqlServerRdbms
   */
  public function getSqlServerExcludedObjects()
  {
    return $this->sqlServerExcludedObjects;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BackfillAllStrategy::class, 'Google_Service_Datastream_BackfillAllStrategy');
