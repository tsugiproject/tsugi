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

class GoogleFirestoreAdminV1BulkDeleteDocumentsRequest extends \Google\Collection
{
  protected $collection_key = 'namespaceIds';
  /**
   * @var string[]
   */
  public $collectionIds;
  /**
   * @var string[]
   */
  public $namespaceIds;

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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirestoreAdminV1BulkDeleteDocumentsRequest::class, 'Google_Service_Firestore_GoogleFirestoreAdminV1BulkDeleteDocumentsRequest');
