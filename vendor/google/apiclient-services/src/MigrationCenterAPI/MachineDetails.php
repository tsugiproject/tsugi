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

namespace Google\Service\MigrationCenterAPI;

class MachineDetails extends \Google\Model
{
  protected $architectureType = MachineArchitectureDetails::class;
  protected $architectureDataType = '';
  /**
   * @var int
   */
  public $coreCount;
  /**
   * @var string
   */
  public $createTime;
  protected $diskPartitionsType = DiskPartitionDetails::class;
  protected $diskPartitionsDataType = '';
  protected $disksType = MachineDiskDetails::class;
  protected $disksDataType = '';
  protected $guestOsType = GuestOsDetails::class;
  protected $guestOsDataType = '';
  /**
   * @var string
   */
  public $machineName;
  /**
   * @var int
   */
  public $memoryMb;
  protected $networkType = MachineNetworkDetails::class;
  protected $networkDataType = '';
  protected $platformType = PlatformDetails::class;
  protected $platformDataType = '';
  /**
   * @var string
   */
  public $powerState;
  /**
   * @var string
   */
  public $uuid;

  /**
   * @param MachineArchitectureDetails
   */
  public function setArchitecture(MachineArchitectureDetails $architecture)
  {
    $this->architecture = $architecture;
  }
  /**
   * @return MachineArchitectureDetails
   */
  public function getArchitecture()
  {
    return $this->architecture;
  }
  /**
   * @param int
   */
  public function setCoreCount($coreCount)
  {
    $this->coreCount = $coreCount;
  }
  /**
   * @return int
   */
  public function getCoreCount()
  {
    return $this->coreCount;
  }
  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param DiskPartitionDetails
   */
  public function setDiskPartitions(DiskPartitionDetails $diskPartitions)
  {
    $this->diskPartitions = $diskPartitions;
  }
  /**
   * @return DiskPartitionDetails
   */
  public function getDiskPartitions()
  {
    return $this->diskPartitions;
  }
  /**
   * @param MachineDiskDetails
   */
  public function setDisks(MachineDiskDetails $disks)
  {
    $this->disks = $disks;
  }
  /**
   * @return MachineDiskDetails
   */
  public function getDisks()
  {
    return $this->disks;
  }
  /**
   * @param GuestOsDetails
   */
  public function setGuestOs(GuestOsDetails $guestOs)
  {
    $this->guestOs = $guestOs;
  }
  /**
   * @return GuestOsDetails
   */
  public function getGuestOs()
  {
    return $this->guestOs;
  }
  /**
   * @param string
   */
  public function setMachineName($machineName)
  {
    $this->machineName = $machineName;
  }
  /**
   * @return string
   */
  public function getMachineName()
  {
    return $this->machineName;
  }
  /**
   * @param int
   */
  public function setMemoryMb($memoryMb)
  {
    $this->memoryMb = $memoryMb;
  }
  /**
   * @return int
   */
  public function getMemoryMb()
  {
    return $this->memoryMb;
  }
  /**
   * @param MachineNetworkDetails
   */
  public function setNetwork(MachineNetworkDetails $network)
  {
    $this->network = $network;
  }
  /**
   * @return MachineNetworkDetails
   */
  public function getNetwork()
  {
    return $this->network;
  }
  /**
   * @param PlatformDetails
   */
  public function setPlatform(PlatformDetails $platform)
  {
    $this->platform = $platform;
  }
  /**
   * @return PlatformDetails
   */
  public function getPlatform()
  {
    return $this->platform;
  }
  /**
   * @param string
   */
  public function setPowerState($powerState)
  {
    $this->powerState = $powerState;
  }
  /**
   * @return string
   */
  public function getPowerState()
  {
    return $this->powerState;
  }
  /**
   * @param string
   */
  public function setUuid($uuid)
  {
    $this->uuid = $uuid;
  }
  /**
   * @return string
   */
  public function getUuid()
  {
    return $this->uuid;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MachineDetails::class, 'Google_Service_MigrationCenterAPI_MachineDetails');
