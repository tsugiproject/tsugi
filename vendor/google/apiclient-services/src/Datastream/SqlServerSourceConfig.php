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

class SqlServerSourceConfig extends \Google\Model
{
  protected $changeTablesType = SqlServerChangeTables::class;
  protected $changeTablesDataType = '';
  protected $excludeObjectsType = SqlServerRdbms::class;
  protected $excludeObjectsDataType = '';
  protected $includeObjectsType = SqlServerRdbms::class;
  protected $includeObjectsDataType = '';
  /**
   * @var int
   */
  public $maxConcurrentBackfillTasks;
  /**
   * @var int
   */
  public $maxConcurrentCdcTasks;
  protected $transactionLogsType = SqlServerTransactionLogs::class;
  protected $transactionLogsDataType = '';

  /**
   * @param SqlServerChangeTables
   */
  public function setChangeTables(SqlServerChangeTables $changeTables)
  {
    $this->changeTables = $changeTables;
  }
  /**
   * @return SqlServerChangeTables
   */
  public function getChangeTables()
  {
    return $this->changeTables;
  }
  /**
   * @param SqlServerRdbms
   */
  public function setExcludeObjects(SqlServerRdbms $excludeObjects)
  {
    $this->excludeObjects = $excludeObjects;
  }
  /**
   * @return SqlServerRdbms
   */
  public function getExcludeObjects()
  {
    return $this->excludeObjects;
  }
  /**
   * @param SqlServerRdbms
   */
  public function setIncludeObjects(SqlServerRdbms $includeObjects)
  {
    $this->includeObjects = $includeObjects;
  }
  /**
   * @return SqlServerRdbms
   */
  public function getIncludeObjects()
  {
    return $this->includeObjects;
  }
  /**
   * @param int
   */
  public function setMaxConcurrentBackfillTasks($maxConcurrentBackfillTasks)
  {
    $this->maxConcurrentBackfillTasks = $maxConcurrentBackfillTasks;
  }
  /**
   * @return int
   */
  public function getMaxConcurrentBackfillTasks()
  {
    return $this->maxConcurrentBackfillTasks;
  }
  /**
   * @param int
   */
  public function setMaxConcurrentCdcTasks($maxConcurrentCdcTasks)
  {
    $this->maxConcurrentCdcTasks = $maxConcurrentCdcTasks;
  }
  /**
   * @return int
   */
  public function getMaxConcurrentCdcTasks()
  {
    return $this->maxConcurrentCdcTasks;
  }
  /**
   * @param SqlServerTransactionLogs
   */
  public function setTransactionLogs(SqlServerTransactionLogs $transactionLogs)
  {
    $this->transactionLogs = $transactionLogs;
  }
  /**
   * @return SqlServerTransactionLogs
   */
  public function getTransactionLogs()
  {
    return $this->transactionLogs;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SqlServerSourceConfig::class, 'Google_Service_Datastream_SqlServerSourceConfig');
