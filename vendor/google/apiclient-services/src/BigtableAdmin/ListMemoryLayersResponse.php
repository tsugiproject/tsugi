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

namespace Google\Service\BigtableAdmin;

class ListMemoryLayersResponse extends \Google\Collection
{
  protected $collection_key = 'memoryLayers';
  /**
   * Locations from which MemoryLayer information could not be retrieved, due to
   * an outage or some other transient condition. MemoryLayers from these
   * locations may be missing from `memory_layers`, or may only have partial
   * information returned. Values are of the form `projects//locations/`
   *
   * @var string[]
   */
  public $failedLocations;
  protected $memoryLayersType = MemoryLayer::class;
  protected $memoryLayersDataType = 'array';
  /**
   * A token, which can be sent as `page_token` to retrieve the next page. If
   * this field is omitted, there are no subsequent pages.
   *
   * @var string
   */
  public $nextPageToken;

  /**
   * Locations from which MemoryLayer information could not be retrieved, due to
   * an outage or some other transient condition. MemoryLayers from these
   * locations may be missing from `memory_layers`, or may only have partial
   * information returned. Values are of the form `projects//locations/`
   *
   * @param string[] $failedLocations
   */
  public function setFailedLocations($failedLocations)
  {
    $this->failedLocations = $failedLocations;
  }
  /**
   * @return string[]
   */
  public function getFailedLocations()
  {
    return $this->failedLocations;
  }
  /**
   * The list of requested memory layers.
   *
   * @param MemoryLayer[] $memoryLayers
   */
  public function setMemoryLayers($memoryLayers)
  {
    $this->memoryLayers = $memoryLayers;
  }
  /**
   * @return MemoryLayer[]
   */
  public function getMemoryLayers()
  {
    return $this->memoryLayers;
  }
  /**
   * A token, which can be sent as `page_token` to retrieve the next page. If
   * this field is omitted, there are no subsequent pages.
   *
   * @param string $nextPageToken
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ListMemoryLayersResponse::class, 'Google_Service_BigtableAdmin_ListMemoryLayersResponse');
