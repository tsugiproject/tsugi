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

namespace Google\Service\Backupdr;

class InitiateBackupResponse extends \Google\Model
{
  /**
   * @var string
   */
  public $backup;
  /**
   * @var int
   */
  public $baseBackupGenerationId;
  /**
   * @var int
   */
  public $newBackupGenerationId;

  /**
   * @param string
   */
  public function setBackup($backup)
  {
    $this->backup = $backup;
  }
  /**
   * @return string
   */
  public function getBackup()
  {
    return $this->backup;
  }
  /**
   * @param int
   */
  public function setBaseBackupGenerationId($baseBackupGenerationId)
  {
    $this->baseBackupGenerationId = $baseBackupGenerationId;
  }
  /**
   * @return int
   */
  public function getBaseBackupGenerationId()
  {
    return $this->baseBackupGenerationId;
  }
  /**
   * @param int
   */
  public function setNewBackupGenerationId($newBackupGenerationId)
  {
    $this->newBackupGenerationId = $newBackupGenerationId;
  }
  /**
   * @return int
   */
  public function getNewBackupGenerationId()
  {
    return $this->newBackupGenerationId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(InitiateBackupResponse::class, 'Google_Service_Backupdr_InitiateBackupResponse');
