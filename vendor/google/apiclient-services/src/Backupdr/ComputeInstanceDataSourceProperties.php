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

namespace Google\Service\Backupdr;

class ComputeInstanceDataSourceProperties extends \Google\Model
{
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $machineType;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $totalDiskCount;
  /**
   * @var string
   */
  public $totalDiskSizeGb;

  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param string
   */
  public function setMachineType($machineType)
  {
    $this->machineType = $machineType;
  }
  /**
   * @return string
   */
  public function getMachineType()
  {
    return $this->machineType;
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
  public function setTotalDiskCount($totalDiskCount)
  {
    $this->totalDiskCount = $totalDiskCount;
  }
  /**
   * @return string
   */
  public function getTotalDiskCount()
  {
    return $this->totalDiskCount;
  }
  /**
   * @param string
   */
  public function setTotalDiskSizeGb($totalDiskSizeGb)
  {
    $this->totalDiskSizeGb = $totalDiskSizeGb;
  }
  /**
   * @return string
   */
  public function getTotalDiskSizeGb()
  {
    return $this->totalDiskSizeGb;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ComputeInstanceDataSourceProperties::class, 'Google_Service_Backupdr_ComputeInstanceDataSourceProperties');
