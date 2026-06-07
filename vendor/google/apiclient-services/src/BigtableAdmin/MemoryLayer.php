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

class MemoryLayer extends \Google\Model
{
  /**
   * The state of the memory layer could not be determined.
   */
  public const STATE_STATE_NOT_KNOWN = 'STATE_NOT_KNOWN';
  /**
   * The memory layer has been successfully enabled and is ready to serve
   * requests.
   */
  public const STATE_READY = 'READY';
  /**
   * The memory layer is currently being enabled, and may be disabled if the
   * enablement process encounters an error. A cluster may not be able to serve
   * requests from the memory layer while being enabled.
   */
  public const STATE_ENABLING = 'ENABLING';
  /**
   * The memory layer is currently being resized, and may revert to its previous
   * storage size if the process encounters an error. The memory layer is still
   * capable of serving requests while being resized, but may exhibit
   * performance as if its number of allocated nodes is between the starting and
   * requested states.
   */
  public const STATE_RESIZING = 'RESIZING';
  /**
   * The memory layer is disabled. The default state for a cluster without a
   * memory layer.
   */
  public const STATE_DISABLED = 'DISABLED';
  /**
   * Optional. The etag for this memory layer. This may be sent on update
   * requests to ensure that the client has an up-to-date value before
   * proceeding. The server returns an ABORTED error on a mismatched etag.
   *
   * @var string
   */
  public $etag;
  protected $memoryConfigType = GoogleBigtableAdminV2MemoryLayerMemoryConfig::class;
  protected $memoryConfigDataType = '';
  /**
   * Identifier. Name of the memory layer. This is always:
   * "projects/{project}/instances/{instance}/clusters/{cluster}/memoryLayer".
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The current state of the memory layer.
   *
   * @var string
   */
  public $state;

  /**
   * Optional. The etag for this memory layer. This may be sent on update
   * requests to ensure that the client has an up-to-date value before
   * proceeding. The server returns an ABORTED error on a mismatched etag.
   *
   * @param string $etag
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * The configuration of this memory layer. Set an empty `memory_config` to
   * enable the memory layer. Unset this to disable the memory layer.
   *
   * @param GoogleBigtableAdminV2MemoryLayerMemoryConfig $memoryConfig
   */
  public function setMemoryConfig(GoogleBigtableAdminV2MemoryLayerMemoryConfig $memoryConfig)
  {
    $this->memoryConfig = $memoryConfig;
  }
  /**
   * @return GoogleBigtableAdminV2MemoryLayerMemoryConfig
   */
  public function getMemoryConfig()
  {
    return $this->memoryConfig;
  }
  /**
   * Identifier. Name of the memory layer. This is always:
   * "projects/{project}/instances/{instance}/clusters/{cluster}/memoryLayer".
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Output only. The current state of the memory layer.
   *
   * Accepted values: STATE_NOT_KNOWN, READY, ENABLING, RESIZING, DISABLED
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MemoryLayer::class, 'Google_Service_BigtableAdmin_MemoryLayer');
