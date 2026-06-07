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

class UpdateBackupConfigRequest extends \Google\Model
{
  protected $backupConfigType = BackupConfig::class;
  protected $backupConfigDataType = '';
  /**
   * Required. Field mask is used to specify the fields to be overwritten in the
   * BackupConfig for the Volume. The fields specified in the update_mask are
   * relative to the resource, not the full request. A field will be overwritten
   * if it is in the mask.
   *
   * @var string
   */
  public $updateMask;
  /**
   * Required. The UUID of the ONTAP-mode volume.
   *
   * @var string
   */
  public $volumeUuid;

  /**
   * Required. Backup configuration to apply.
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
   * Required. Field mask is used to specify the fields to be overwritten in the
   * BackupConfig for the Volume. The fields specified in the update_mask are
   * relative to the resource, not the full request. A field will be overwritten
   * if it is in the mask.
   *
   * @param string $updateMask
   */
  public function setUpdateMask($updateMask)
  {
    $this->updateMask = $updateMask;
  }
  /**
   * @return string
   */
  public function getUpdateMask()
  {
    return $this->updateMask;
  }
  /**
   * Required. The UUID of the ONTAP-mode volume.
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
class_alias(UpdateBackupConfigRequest::class, 'Google_Service_NetAppFiles_UpdateBackupConfigRequest');
