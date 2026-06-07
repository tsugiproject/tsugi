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

namespace Google\Service\StorageBatchOperations;

class CustomContextUpdates extends \Google\Collection
{
  protected $collection_key = 'keysToClear';
  /**
   * Optional. Custom contexts to clear by key. A key cannot be present in both
   * `updates` and `keys_to_clear`.
   *
   * @var string[]
   */
  public $keysToClear;
  protected $updatesType = ObjectCustomContextPayload::class;
  protected $updatesDataType = 'map';

  /**
   * Optional. Custom contexts to clear by key. A key cannot be present in both
   * `updates` and `keys_to_clear`.
   *
   * @param string[] $keysToClear
   */
  public function setKeysToClear($keysToClear)
  {
    $this->keysToClear = $keysToClear;
  }
  /**
   * @return string[]
   */
  public function getKeysToClear()
  {
    return $this->keysToClear;
  }
  /**
   * Optional. Insert or update the existing custom contexts.
   *
   * @param ObjectCustomContextPayload[] $updates
   */
  public function setUpdates($updates)
  {
    $this->updates = $updates;
  }
  /**
   * @return ObjectCustomContextPayload[]
   */
  public function getUpdates()
  {
    return $this->updates;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CustomContextUpdates::class, 'Google_Service_StorageBatchOperations_CustomContextUpdates');
