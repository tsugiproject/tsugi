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

namespace Google\Service\DataprocMetastore;

class GoogleCloudMetastoreV2RestoreServiceRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $backup;
  /**
   * @var string
   */
  public $backupLocation;
  /**
   * @var string
   */
  public $requestId;
  /**
   * @var string
   */
  public $restoreType;

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
   * @param string
   */
  public function setBackupLocation($backupLocation)
  {
    $this->backupLocation = $backupLocation;
  }
  /**
   * @return string
   */
  public function getBackupLocation()
  {
    return $this->backupLocation;
  }
  /**
   * @param string
   */
  public function setRequestId($requestId)
  {
    $this->requestId = $requestId;
  }
  /**
   * @return string
   */
  public function getRequestId()
  {
    return $this->requestId;
  }
  /**
   * @param string
   */
  public function setRestoreType($restoreType)
  {
    $this->restoreType = $restoreType;
  }
  /**
   * @return string
   */
  public function getRestoreType()
  {
    return $this->restoreType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudMetastoreV2RestoreServiceRequest::class, 'Google_Service_DataprocMetastore_GoogleCloudMetastoreV2RestoreServiceRequest');
