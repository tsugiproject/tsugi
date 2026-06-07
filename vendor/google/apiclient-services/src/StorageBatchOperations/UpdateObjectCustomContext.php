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

class UpdateObjectCustomContext extends \Google\Model
{
  /**
   * If set, must be set to true and all existing object custom contexts will be
   * deleted.
   *
   * @var bool
   */
  public $clearAll;
  protected $customContextUpdatesType = CustomContextUpdates::class;
  protected $customContextUpdatesDataType = '';

  /**
   * If set, must be set to true and all existing object custom contexts will be
   * deleted.
   *
   * @param bool $clearAll
   */
  public function setClearAll($clearAll)
  {
    $this->clearAll = $clearAll;
  }
  /**
   * @return bool
   */
  public function getClearAll()
  {
    return $this->clearAll;
  }
  /**
   * A collection of updates to apply to specific custom contexts. Use this to
   * add, update or delete individual contexts by key.
   *
   * @param CustomContextUpdates $customContextUpdates
   */
  public function setCustomContextUpdates(CustomContextUpdates $customContextUpdates)
  {
    $this->customContextUpdates = $customContextUpdates;
  }
  /**
   * @return CustomContextUpdates
   */
  public function getCustomContextUpdates()
  {
    return $this->customContextUpdates;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(UpdateObjectCustomContext::class, 'Google_Service_StorageBatchOperations_UpdateObjectCustomContext');
