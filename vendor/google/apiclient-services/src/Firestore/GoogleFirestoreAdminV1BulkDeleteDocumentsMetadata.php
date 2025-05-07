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

namespace Google\Service\Firestore;

class GoogleFirestoreAdminV1BulkDeleteDocumentsMetadata extends \Google\Collection
{
  protected $collection_key = 'namespaceIds';
  /**
   * @var string[]
   */
  public $collectionIds;
  /**
   * @var string
   */
  public $endTime;
  /**
   * @var string[]
   */
  public $namespaceIds;
  /**
   * @var string
   */
  public $operationState;
  protected $progressBytesType = GoogleFirestoreAdminV1Progress::class;
  protected $progressBytesDataType = '';
  protected $progressDocumentsType = GoogleFirestoreAdminV1Progress::class;
  protected $progressDocumentsDataType = '';
  /**
   * @var string
   */
  public $snapshotTime;
  /**
   * @var string
   */
  public $startTime;

  /**
   * @param string[]
   */
  public function setCollectionIds($collectionIds)
  {
    $this->collectionIds = $collectionIds;
  }
  /**
   * @return string[]
   */
  public function getCollectionIds()
  {
    return $this->collectionIds;
  }
  /**
   * @param string
   */
  public function setEndTime($endTime)
  {
    $this->endTime = $endTime;
  }
  /**
   * @return string
   */
  public function getEndTime()
  {
    return $this->endTime;
  }
  /**
   * @param string[]
   */
  public function setNamespaceIds($namespaceIds)
  {
    $this->namespaceIds = $namespaceIds;
  }
  /**
   * @return string[]
   */
  public function getNamespaceIds()
  {
    return $this->namespaceIds;
  }
  /**
   * @param string
   */
  public function setOperationState($operationState)
  {
    $this->operationState = $operationState;
  }
  /**
   * @return string
   */
  public function getOperationState()
  {
    return $this->operationState;
  }
  /**
   * @param GoogleFirestoreAdminV1Progress
   */
  public function setProgressBytes(GoogleFirestoreAdminV1Progress $progressBytes)
  {
    $this->progressBytes = $progressBytes;
  }
  /**
   * @return GoogleFirestoreAdminV1Progress
   */
  public function getProgressBytes()
  {
    return $this->progressBytes;
  }
  /**
   * @param GoogleFirestoreAdminV1Progress
   */
  public function setProgressDocuments(GoogleFirestoreAdminV1Progress $progressDocuments)
  {
    $this->progressDocuments = $progressDocuments;
  }
  /**
   * @return GoogleFirestoreAdminV1Progress
   */
  public function getProgressDocuments()
  {
    return $this->progressDocuments;
  }
  /**
   * @param string
   */
  public function setSnapshotTime($snapshotTime)
  {
    $this->snapshotTime = $snapshotTime;
  }
  /**
   * @return string
   */
  public function getSnapshotTime()
  {
    return $this->snapshotTime;
  }
  /**
   * @param string
   */
  public function setStartTime($startTime)
  {
    $this->startTime = $startTime;
  }
  /**
   * @return string
   */
  public function getStartTime()
  {
    return $this->startTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirestoreAdminV1BulkDeleteDocumentsMetadata::class, 'Google_Service_Firestore_GoogleFirestoreAdminV1BulkDeleteDocumentsMetadata');
