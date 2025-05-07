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

namespace Google\Service\Dataproc;

class DiskConfig extends \Google\Model
{
  /**
   * @var string
   */
  public $bootDiskProvisionedIops;
  /**
   * @var string
   */
  public $bootDiskProvisionedThroughput;
  /**
   * @var int
   */
  public $bootDiskSizeGb;
  /**
   * @var string
   */
  public $bootDiskType;
  /**
   * @var string
   */
  public $localSsdInterface;
  /**
   * @var int
   */
  public $numLocalSsds;

  /**
   * @param string
   */
  public function setBootDiskProvisionedIops($bootDiskProvisionedIops)
  {
    $this->bootDiskProvisionedIops = $bootDiskProvisionedIops;
  }
  /**
   * @return string
   */
  public function getBootDiskProvisionedIops()
  {
    return $this->bootDiskProvisionedIops;
  }
  /**
   * @param string
   */
  public function setBootDiskProvisionedThroughput($bootDiskProvisionedThroughput)
  {
    $this->bootDiskProvisionedThroughput = $bootDiskProvisionedThroughput;
  }
  /**
   * @return string
   */
  public function getBootDiskProvisionedThroughput()
  {
    return $this->bootDiskProvisionedThroughput;
  }
  /**
   * @param int
   */
  public function setBootDiskSizeGb($bootDiskSizeGb)
  {
    $this->bootDiskSizeGb = $bootDiskSizeGb;
  }
  /**
   * @return int
   */
  public function getBootDiskSizeGb()
  {
    return $this->bootDiskSizeGb;
  }
  /**
   * @param string
   */
  public function setBootDiskType($bootDiskType)
  {
    $this->bootDiskType = $bootDiskType;
  }
  /**
   * @return string
   */
  public function getBootDiskType()
  {
    return $this->bootDiskType;
  }
  /**
   * @param string
   */
  public function setLocalSsdInterface($localSsdInterface)
  {
    $this->localSsdInterface = $localSsdInterface;
  }
  /**
   * @return string
   */
  public function getLocalSsdInterface()
  {
    return $this->localSsdInterface;
  }
  /**
   * @param int
   */
  public function setNumLocalSsds($numLocalSsds)
  {
    $this->numLocalSsds = $numLocalSsds;
  }
  /**
   * @return int
   */
  public function getNumLocalSsds()
  {
    return $this->numLocalSsds;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DiskConfig::class, 'Google_Service_Dataproc_DiskConfig');
