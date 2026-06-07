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

class BackupSource extends \Google\Collection
{
  protected $collection_key = 'fileList';
  /**
   * Required. The backup resource name.
   *
   * @var string
   */
  public $backup;
  /**
   * Optional. List of files to be restored in the form of their absolute path
   * as in source volume. If provided, only these files will be restored. If not
   * provided, the entire backup will be restored (Full Backup Restore)
   *
   * @var string[]
   */
  public $fileList;

  /**
   * Required. The backup resource name.
   *
   * @param string $backup
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
   * Optional. List of files to be restored in the form of their absolute path
   * as in source volume. If provided, only these files will be restored. If not
   * provided, the entire backup will be restored (Full Backup Restore)
   *
   * @param string[] $fileList
   */
  public function setFileList($fileList)
  {
    $this->fileList = $fileList;
  }
  /**
   * @return string[]
   */
  public function getFileList()
  {
    return $this->fileList;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BackupSource::class, 'Google_Service_NetAppFiles_BackupSource');
