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

class OracleSourceConfig extends \Google\Model
{
  protected $binaryLogParserType = BinaryLogParser::class;
  protected $binaryLogParserDataType = '';
  protected $dropLargeObjectsType = DropLargeObjects::class;
  protected $dropLargeObjectsDataType = '';
  protected $excludeObjectsType = OracleRdbms::class;
  protected $excludeObjectsDataType = '';
  protected $includeObjectsType = OracleRdbms::class;
  protected $includeObjectsDataType = '';
  protected $logMinerType = LogMiner::class;
  protected $logMinerDataType = '';
  /**
   * @var int
   */
  public $maxConcurrentBackfillTasks;
  /**
   * @var int
   */
  public $maxConcurrentCdcTasks;
  protected $streamLargeObjectsType = StreamLargeObjects::class;
  protected $streamLargeObjectsDataType = '';

  /**
   * @param BinaryLogParser
   */
  public function setBinaryLogParser(BinaryLogParser $binaryLogParser)
  {
    $this->binaryLogParser = $binaryLogParser;
  }
  /**
   * @return BinaryLogParser
   */
  public function getBinaryLogParser()
  {
    return $this->binaryLogParser;
  }
  /**
   * @param DropLargeObjects
   */
  public function setDropLargeObjects(DropLargeObjects $dropLargeObjects)
  {
    $this->dropLargeObjects = $dropLargeObjects;
  }
  /**
   * @return DropLargeObjects
   */
  public function getDropLargeObjects()
  {
    return $this->dropLargeObjects;
  }
  /**
   * @param OracleRdbms
   */
  public function setExcludeObjects(OracleRdbms $excludeObjects)
  {
    $this->excludeObjects = $excludeObjects;
  }
  /**
   * @return OracleRdbms
   */
  public function getExcludeObjects()
  {
    return $this->excludeObjects;
  }
  /**
   * @param OracleRdbms
   */
  public function setIncludeObjects(OracleRdbms $includeObjects)
  {
    $this->includeObjects = $includeObjects;
  }
  /**
   * @return OracleRdbms
   */
  public function getIncludeObjects()
  {
    return $this->includeObjects;
  }
  /**
   * @param LogMiner
   */
  public function setLogMiner(LogMiner $logMiner)
  {
    $this->logMiner = $logMiner;
  }
  /**
   * @return LogMiner
   */
  public function getLogMiner()
  {
    return $this->logMiner;
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
   * @param StreamLargeObjects
   */
  public function setStreamLargeObjects(StreamLargeObjects $streamLargeObjects)
  {
    $this->streamLargeObjects = $streamLargeObjects;
  }
  /**
   * @return StreamLargeObjects
   */
  public function getStreamLargeObjects()
  {
    return $this->streamLargeObjects;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(OracleSourceConfig::class, 'Google_Service_Datastream_OracleSourceConfig');
