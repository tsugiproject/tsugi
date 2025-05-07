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

class GoogleCloudMetastoreV2ImportMetadataRequest extends \Google\Model
{
  protected $databaseDumpType = GoogleCloudMetastoreV2DatabaseDump::class;
  protected $databaseDumpDataType = '';
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $requestId;

  /**
   * @param GoogleCloudMetastoreV2DatabaseDump
   */
  public function setDatabaseDump(GoogleCloudMetastoreV2DatabaseDump $databaseDump)
  {
    $this->databaseDump = $databaseDump;
  }
  /**
   * @return GoogleCloudMetastoreV2DatabaseDump
   */
  public function getDatabaseDump()
  {
    return $this->databaseDump;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudMetastoreV2ImportMetadataRequest::class, 'Google_Service_DataprocMetastore_GoogleCloudMetastoreV2ImportMetadataRequest');
