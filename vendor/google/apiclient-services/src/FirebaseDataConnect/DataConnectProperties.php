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

namespace Google\Service\FirebaseDataConnect;

class DataConnectProperties extends \Google\Collection
{
  protected $collection_key = 'path';
  /**
   * A single Entity ID. Set if the path points to a single entity.
   *
   * @var string
   */
  public $entityId;
  /**
   * A list of Entity IDs. Set if the path points to an array of entities. An ID
   * is present for each element of the array at the corresponding index.
   *
   * @var string[]
   */
  public $entityIds;
  /**
   * The server-suggested duration before data under path is considered stale.
   *
   * @var string
   */
  public $maxAge;
  /**
   * The path under response.data where the rest of the fields apply. Each
   * element may be a string (field name) or number (array index). The root of
   * response.data is denoted by the empty list `[]`.
   *
   * @var array[]
   */
  public $path;

  /**
   * A single Entity ID. Set if the path points to a single entity.
   *
   * @param string $entityId
   */
  public function setEntityId($entityId)
  {
    $this->entityId = $entityId;
  }
  /**
   * @return string
   */
  public function getEntityId()
  {
    return $this->entityId;
  }
  /**
   * A list of Entity IDs. Set if the path points to an array of entities. An ID
   * is present for each element of the array at the corresponding index.
   *
   * @param string[] $entityIds
   */
  public function setEntityIds($entityIds)
  {
    $this->entityIds = $entityIds;
  }
  /**
   * @return string[]
   */
  public function getEntityIds()
  {
    return $this->entityIds;
  }
  /**
   * The server-suggested duration before data under path is considered stale.
   *
   * @param string $maxAge
   */
  public function setMaxAge($maxAge)
  {
    $this->maxAge = $maxAge;
  }
  /**
   * @return string
   */
  public function getMaxAge()
  {
    return $this->maxAge;
  }
  /**
   * The path under response.data where the rest of the fields apply. Each
   * element may be a string (field name) or number (array index). The root of
   * response.data is denoted by the empty list `[]`.
   *
   * @param array[] $path
   */
  public function setPath($path)
  {
    $this->path = $path;
  }
  /**
   * @return array[]
   */
  public function getPath()
  {
    return $this->path;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DataConnectProperties::class, 'Google_Service_FirebaseDataConnect_DataConnectProperties');
