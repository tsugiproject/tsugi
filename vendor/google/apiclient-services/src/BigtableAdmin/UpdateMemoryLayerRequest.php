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

class UpdateMemoryLayerRequest extends \Google\Model
{
  protected $memoryLayerType = MemoryLayer::class;
  protected $memoryLayerDataType = '';
  /**
   * Optional. The list of fields to update.
   *
   * @var string
   */
  public $updateMask;

  /**
   * Required. The memory layer to update. The memory layer's `name` format is
   * as follows:
   * `projects/{project}/instances/{instance}/clusters/{cluster}/memoryLayer`.
   *
   * @param MemoryLayer $memoryLayer
   */
  public function setMemoryLayer(MemoryLayer $memoryLayer)
  {
    $this->memoryLayer = $memoryLayer;
  }
  /**
   * @return MemoryLayer
   */
  public function getMemoryLayer()
  {
    return $this->memoryLayer;
  }
  /**
   * Optional. The list of fields to update.
   *
   * @param string $updateMask
   */
  public function setUpdateMask($updateMask)
  {
    $this->updateMask = $updateMask;
  }
  /**
   * @return string
   */
  public function getUpdateMask()
  {
    return $this->updateMask;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(UpdateMemoryLayerRequest::class, 'Google_Service_BigtableAdmin_UpdateMemoryLayerRequest');
