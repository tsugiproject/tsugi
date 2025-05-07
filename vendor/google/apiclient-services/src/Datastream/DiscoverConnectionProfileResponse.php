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

class DiscoverConnectionProfileResponse extends \Google\Model
{
  protected $mysqlRdbmsType = MysqlRdbms::class;
  protected $mysqlRdbmsDataType = '';
  protected $oracleRdbmsType = OracleRdbms::class;
  protected $oracleRdbmsDataType = '';
  protected $postgresqlRdbmsType = PostgresqlRdbms::class;
  protected $postgresqlRdbmsDataType = '';
  protected $sqlServerRdbmsType = SqlServerRdbms::class;
  protected $sqlServerRdbmsDataType = '';

  /**
   * @param MysqlRdbms
   */
  public function setMysqlRdbms(MysqlRdbms $mysqlRdbms)
  {
    $this->mysqlRdbms = $mysqlRdbms;
  }
  /**
   * @return MysqlRdbms
   */
  public function getMysqlRdbms()
  {
    return $this->mysqlRdbms;
  }
  /**
   * @param OracleRdbms
   */
  public function setOracleRdbms(OracleRdbms $oracleRdbms)
  {
    $this->oracleRdbms = $oracleRdbms;
  }
  /**
   * @return OracleRdbms
   */
  public function getOracleRdbms()
  {
    return $this->oracleRdbms;
  }
  /**
   * @param PostgresqlRdbms
   */
  public function setPostgresqlRdbms(PostgresqlRdbms $postgresqlRdbms)
  {
    $this->postgresqlRdbms = $postgresqlRdbms;
  }
  /**
   * @return PostgresqlRdbms
   */
  public function getPostgresqlRdbms()
  {
    return $this->postgresqlRdbms;
  }
  /**
   * @param SqlServerRdbms
   */
  public function setSqlServerRdbms(SqlServerRdbms $sqlServerRdbms)
  {
    $this->sqlServerRdbms = $sqlServerRdbms;
  }
  /**
   * @return SqlServerRdbms
   */
  public function getSqlServerRdbms()
  {
    return $this->sqlServerRdbms;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DiscoverConnectionProfileResponse::class, 'Google_Service_Datastream_DiscoverConnectionProfileResponse');
