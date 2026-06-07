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

class OntapSource extends \Google\Model
{
  /**
   * Optional. The UUID of the ONTAP source snapshot.
   *
   * @var string
   */
  public $snapshotUuid;
  /**
   * Required. Name of the storage pool. This must be specified for creating
   * backups for ONTAP mode volumes. Format: `projects/{projects_id}/locations/{
   * location}/storagePools/{storage_pool_id}`
   *
   * @var string
   */
  public $storagePool;
  /**
   * Required. The UUID of the ONTAP source volume.
   *
   * @var string
   */
  public $volumeUuid;

  /**
   * Optional. The UUID of the ONTAP source snapshot.
   *
   * @param string $snapshotUuid
   */
  public function setSnapshotUuid($snapshotUuid)
  {
    $this->snapshotUuid = $snapshotUuid;
  }
  /**
   * @return string
   */
  public function getSnapshotUuid()
  {
    return $this->snapshotUuid;
  }
  /**
   * Required. Name of the storage pool. This must be specified for creating
   * backups for ONTAP mode volumes. Format: `projects/{projects_id}/locations/{
   * location}/storagePools/{storage_pool_id}`
   *
   * @param string $storagePool
   */
  public function setStoragePool($storagePool)
  {
    $this->storagePool = $storagePool;
  }
  /**
   * @return string
   */
  public function getStoragePool()
  {
    return $this->storagePool;
  }
  /**
   * Required. The UUID of the ONTAP source volume.
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
class_alias(OntapSource::class, 'Google_Service_NetAppFiles_OntapSource');
