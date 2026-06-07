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

namespace Google\Service\OracleDatabase;

class GoldengateBackupSchedule extends \Google\Model
{
  /**
   * The frequency backup scheduled is unspecified.
   */
  public const FREQUENCY_BACKUP_SCHEDULED_FREQUENCY_BACKUP_SCHEDULED_UNSPECIFIED = 'FREQUENCY_BACKUP_SCHEDULED_UNSPECIFIED';
  /**
   * The frequency backup scheduled is daily.
   */
  public const FREQUENCY_BACKUP_SCHEDULED_DAILY = 'DAILY';
  /**
   * The frequency backup scheduled is weekly.
   */
  public const FREQUENCY_BACKUP_SCHEDULED_WEEKLY = 'WEEKLY';
  /**
   * The frequency backup scheduled is monthly.
   */
  public const FREQUENCY_BACKUP_SCHEDULED_MONTHLY = 'MONTHLY';
  /**
   * Output only. The timestamp of when the backup was scheduled.
   *
   * @var string
   */
  public $backupScheduledTime;
  /**
   * Output only. The bucket name.
   *
   * @var string
   */
  public $bucket;
  /**
   * Output only. The compartment id.
   *
   * @var string
   */
  public $compartmentId;
  /**
   * Output only. The frequency backup scheduled.
   *
   * @var string
   */
  public $frequencyBackupScheduled;
  /**
   * Output only. If metadata only.
   *
   * @var bool
   */
  public $metadataOnly;
  /**
   * Output only. The namespace name.
   *
   * @var string
   */
  public $namespace;

  /**
   * Output only. The timestamp of when the backup was scheduled.
   *
   * @param string $backupScheduledTime
   */
  public function setBackupScheduledTime($backupScheduledTime)
  {
    $this->backupScheduledTime = $backupScheduledTime;
  }
  /**
   * @return string
   */
  public function getBackupScheduledTime()
  {
    return $this->backupScheduledTime;
  }
  /**
   * Output only. The bucket name.
   *
   * @param string $bucket
   */
  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  /**
   * @return string
   */
  public function getBucket()
  {
    return $this->bucket;
  }
  /**
   * Output only. The compartment id.
   *
   * @param string $compartmentId
   */
  public function setCompartmentId($compartmentId)
  {
    $this->compartmentId = $compartmentId;
  }
  /**
   * @return string
   */
  public function getCompartmentId()
  {
    return $this->compartmentId;
  }
  /**
   * Output only. The frequency backup scheduled.
   *
   * Accepted values: FREQUENCY_BACKUP_SCHEDULED_UNSPECIFIED, DAILY, WEEKLY,
   * MONTHLY
   *
   * @param self::FREQUENCY_BACKUP_SCHEDULED_* $frequencyBackupScheduled
   */
  public function setFrequencyBackupScheduled($frequencyBackupScheduled)
  {
    $this->frequencyBackupScheduled = $frequencyBackupScheduled;
  }
  /**
   * @return self::FREQUENCY_BACKUP_SCHEDULED_*
   */
  public function getFrequencyBackupScheduled()
  {
    return $this->frequencyBackupScheduled;
  }
  /**
   * Output only. If metadata only.
   *
   * @param bool $metadataOnly
   */
  public function setMetadataOnly($metadataOnly)
  {
    $this->metadataOnly = $metadataOnly;
  }
  /**
   * @return bool
   */
  public function getMetadataOnly()
  {
    return $this->metadataOnly;
  }
  /**
   * Output only. The namespace name.
   *
   * @param string $namespace
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;
  }
  /**
   * @return string
   */
  public function getNamespace()
  {
    return $this->namespace;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateBackupSchedule::class, 'Google_Service_OracleDatabase_GoldengateBackupSchedule');
