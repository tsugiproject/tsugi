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

namespace Google\Service\OnDemandScanning;

class GrafeasV1BaseImage extends \Google\Model
{
  /**
   * @var int
   */
  public $layerCount;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $repository;

  /**
   * @param int
   */
  public function setLayerCount($layerCount)
  {
    $this->layerCount = $layerCount;
  }
  /**
   * @return int
   */
  public function getLayerCount()
  {
    return $this->layerCount;
  }
  /**
   * @param string
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
   * @param string
   */
  public function setRepository($repository)
  {
    $this->repository = $repository;
  }
  /**
   * @return string
   */
  public function getRepository()
  {
    return $this->repository;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GrafeasV1BaseImage::class, 'Google_Service_OnDemandScanning_GrafeasV1BaseImage');
