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

namespace Google\Service\NetAppFiles;

class VolumeBackupConfig extends \Google\Model
{
  protected $backupConfigType = BackupConfig::class;
  protected $backupConfigDataType = '';
  /**
   * Provides the Ontap UUID of the volume within the pool.
   *
   * @var string
   */
  public $volumeUuid;

  /**
   * Backup configuration for the volume.
   *
   * @param BackupConfig $backupConfig
   */
  public function setBackupConfig(BackupConfig $backupConfig)
  {
    $this->backupConfig = $backupConfig;
  }
  /**
   * @return BackupConfig
   */
  public function getBackupConfig()
  {
    return $this->backupConfig;
  }
  /**
   * Provides the Ontap UUID of the volume within the pool.
   *
   * @param string $volumeUuid
   */
  public function setVolumeUuid($volumeUuid)
  {
    $this->volumeUuid = $volumeUuid;
  }
  /**
   * @return string
   */
  public function getVolumeUuid()
  {
    return $this->volumeUuid;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(VolumeBackupConfig::class, 'Google_Service_NetAppFiles_VolumeBackupConfig');
