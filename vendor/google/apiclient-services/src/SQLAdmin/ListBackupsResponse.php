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

namespace Google\Service\SQLAdmin;

class ListBackupsResponse extends \Google\Collection
{
  protected $collection_key = 'warnings';
  protected $backupsType = Backup::class;
  protected $backupsDataType = 'array';
  /**
   * @var string
   */
  public $nextPageToken;
  protected $warningsType = ApiWarning::class;
  protected $warningsDataType = 'array';

  /**
   * @param Backup[]
   */
  public function setBackups($backups)
  {
    $this->backups = $backups;
  }
  /**
   * @return Backup[]
   */
  public function getBackups()
  {
    return $this->backups;
  }
  /**
   * @param string
   */
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  /**
   * @return string
   */
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * @param ApiWarning[]
   */
  public function setWarnings($warnings)
  {
    $this->warnings = $warnings;
  }
  /**
   * @return ApiWarning[]
   */
  public function getWarnings()
  {
    return $this->warnings;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ListBackupsResponse::class, 'Google_Service_SQLAdmin_ListBackupsResponse');
