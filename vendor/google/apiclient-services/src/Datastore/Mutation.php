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

namespace Google\Service\Datastore;

class Mutation extends \Google\Collection
{
  protected $collection_key = 'propertyTransforms';
  /**
   * @var string
   */
  public $baseVersion;
  /**
   * @var string
   */
  public $conflictResolutionStrategy;
  protected $deleteType = Key::class;
  protected $deleteDataType = '';
  protected $insertType = Entity::class;
  protected $insertDataType = '';
  protected $propertyMaskType = PropertyMask::class;
  protected $propertyMaskDataType = '';
  protected $propertyTransformsType = PropertyTransform::class;
  protected $propertyTransformsDataType = 'array';
  protected $updateType = Entity::class;
  protected $updateDataType = '';
  /**
   * @var string
   */
  public $updateTime;
  protected $upsertType = Entity::class;
  protected $upsertDataType = '';

  /**
   * @param string
   */
  public function setBaseVersion($baseVersion)
  {
    $this->baseVersion = $baseVersion;
  }
  /**
   * @return string
   */
  public function getBaseVersion()
  {
    return $this->baseVersion;
  }
  /**
   * @param string
   */
  public function setConflictResolutionStrategy($conflictResolutionStrategy)
  {
    $this->conflictResolutionStrategy = $conflictResolutionStrategy;
  }
  /**
   * @return string
   */
  public function getConflictResolutionStrategy()
  {
    return $this->conflictResolutionStrategy;
  }
  /**
   * @param Key
   */
  public function setDelete(Key $delete)
  {
    $this->delete = $delete;
  }
  /**
   * @return Key
   */
  public function getDelete()
  {
    return $this->delete;
  }
  /**
   * @param Entity
   */
  public function setInsert(Entity $insert)
  {
    $this->insert = $insert;
  }
  /**
   * @return Entity
   */
  public function getInsert()
  {
    return $this->insert;
  }
  /**
   * @param PropertyMask
   */
  public function setPropertyMask(PropertyMask $propertyMask)
  {
    $this->propertyMask = $propertyMask;
  }
  /**
   * @return PropertyMask
   */
  public function getPropertyMask()
  {
    return $this->propertyMask;
  }
  /**
   * @param PropertyTransform[]
   */
  public function setPropertyTransforms($propertyTransforms)
  {
    $this->propertyTransforms = $propertyTransforms;
  }
  /**
   * @return PropertyTransform[]
   */
  public function getPropertyTransforms()
  {
    return $this->propertyTransforms;
  }
  /**
   * @param Entity
   */
  public function setUpdate(Entity $update)
  {
    $this->update = $update;
  }
  /**
   * @return Entity
   */
  public function getUpdate()
  {
    return $this->update;
  }
  /**
   * @param string
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
  /**
   * @param Entity
   */
  public function setUpsert(Entity $upsert)
  {
    $this->upsert = $upsert;
  }
  /**
   * @return Entity
   */
  public function getUpsert()
  {
    return $this->upsert;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Mutation::class, 'Google_Service_Datastore_Mutation');
