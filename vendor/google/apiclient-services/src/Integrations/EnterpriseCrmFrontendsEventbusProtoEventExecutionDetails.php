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

namespace Google\Service\Integrations;

class EnterpriseCrmFrontendsEventbusProtoEventExecutionDetails extends \Google\Collection
{
  protected $collection_key = 'eventExecutionSnapshot';
  /**
   * @var string
   */
  public $cancelReason;
  protected $eventAttemptStatsType = EnterpriseCrmEventbusProtoEventExecutionDetailsEventAttemptStats::class;
  protected $eventAttemptStatsDataType = 'array';
  protected $eventExecutionSnapshotType = EnterpriseCrmFrontendsEventbusProtoEventExecutionSnapshot::class;
  protected $eventExecutionSnapshotDataType = 'array';
  /**
   * @var string
   */
  public $eventExecutionSnapshotsSize;
  /**
   * @var string
   */
  public $eventExecutionState;
  /**
   * @var int
   */
  public $eventRetriesFromBeginningCount;
  /**
   * @var string
   */
  public $logFilePath;
  /**
   * @var string
   */
  public $networkAddress;
  /**
   * @var string
   */
  public $nextExecutionTime;
  /**
   * @var int
   */
  public $ryeLockUnheldCount;

  /**
   * @param string
   */
  public function setCancelReason($cancelReason)
  {
    $this->cancelReason = $cancelReason;
  }
  /**
   * @return string
   */
  public function getCancelReason()
  {
    return $this->cancelReason;
  }
  /**
   * @param EnterpriseCrmEventbusProtoEventExecutionDetailsEventAttemptStats[]
   */
  public function setEventAttemptStats($eventAttemptStats)
  {
    $this->eventAttemptStats = $eventAttemptStats;
  }
  /**
   * @return EnterpriseCrmEventbusProtoEventExecutionDetailsEventAttemptStats[]
   */
  public function getEventAttemptStats()
  {
    return $this->eventAttemptStats;
  }
  /**
   * @param EnterpriseCrmFrontendsEventbusProtoEventExecutionSnapshot[]
   */
  public function setEventExecutionSnapshot($eventExecutionSnapshot)
  {
    $this->eventExecutionSnapshot = $eventExecutionSnapshot;
  }
  /**
   * @return EnterpriseCrmFrontendsEventbusProtoEventExecutionSnapshot[]
   */
  public function getEventExecutionSnapshot()
  {
    return $this->eventExecutionSnapshot;
  }
  /**
   * @param string
   */
  public function setEventExecutionSnapshotsSize($eventExecutionSnapshotsSize)
  {
    $this->eventExecutionSnapshotsSize = $eventExecutionSnapshotsSize;
  }
  /**
   * @return string
   */
  public function getEventExecutionSnapshotsSize()
  {
    return $this->eventExecutionSnapshotsSize;
  }
  /**
   * @param string
   */
  public function setEventExecutionState($eventExecutionState)
  {
    $this->eventExecutionState = $eventExecutionState;
  }
  /**
   * @return string
   */
  public function getEventExecutionState()
  {
    return $this->eventExecutionState;
  }
  /**
   * @param int
   */
  public function setEventRetriesFromBeginningCount($eventRetriesFromBeginningCount)
  {
    $this->eventRetriesFromBeginningCount = $eventRetriesFromBeginningCount;
  }
  /**
   * @return int
   */
  public function getEventRetriesFromBeginningCount()
  {
    return $this->eventRetriesFromBeginningCount;
  }
  /**
   * @param string
   */
  public function setLogFilePath($logFilePath)
  {
    $this->logFilePath = $logFilePath;
  }
  /**
   * @return string
   */
  public function getLogFilePath()
  {
    return $this->logFilePath;
  }
  /**
   * @param string
   */
  public function setNetworkAddress($networkAddress)
  {
    $this->networkAddress = $networkAddress;
  }
  /**
   * @return string
   */
  public function getNetworkAddress()
  {
    return $this->networkAddress;
  }
  /**
   * @param string
   */
  public function setNextExecutionTime($nextExecutionTime)
  {
    $this->nextExecutionTime = $nextExecutionTime;
  }
  /**
   * @return string
   */
  public function getNextExecutionTime()
  {
    return $this->nextExecutionTime;
  }
  /**
   * @param int
   */
  public function setRyeLockUnheldCount($ryeLockUnheldCount)
  {
    $this->ryeLockUnheldCount = $ryeLockUnheldCount;
  }
  /**
   * @return int
   */
  public function getRyeLockUnheldCount()
  {
    return $this->ryeLockUnheldCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EnterpriseCrmFrontendsEventbusProtoEventExecutionDetails::class, 'Google_Service_Integrations_EnterpriseCrmFrontendsEventbusProtoEventExecutionDetails');
