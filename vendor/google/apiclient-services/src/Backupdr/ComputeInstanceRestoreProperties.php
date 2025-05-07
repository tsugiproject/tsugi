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

class ComputeInstanceRestoreProperties extends \Google\Collection
{
  protected $collection_key = 'serviceAccounts';
  protected $advancedMachineFeaturesType = AdvancedMachineFeatures::class;
  protected $advancedMachineFeaturesDataType = '';
  /**
   * @var bool
   */
  public $canIpForward;
  protected $confidentialInstanceConfigType = ConfidentialInstanceConfig::class;
  protected $confidentialInstanceConfigDataType = '';
  /**
   * @var bool
   */
  public $deletionProtection;
  /**
   * @var string
   */
  public $description;
  protected $disksType = AttachedDisk::class;
  protected $disksDataType = 'array';
  protected $displayDeviceType = DisplayDevice::class;
  protected $displayDeviceDataType = '';
  protected $guestAcceleratorsType = AcceleratorConfig::class;
  protected $guestAcceleratorsDataType = 'array';
  /**
   * @var string
   */
  public $hostname;
  protected $instanceEncryptionKeyType = CustomerEncryptionKey::class;
  protected $instanceEncryptionKeyDataType = '';
  /**
   * @var string
   */
  public $keyRevocationActionType;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $machineType;
  protected $metadataType = Metadata::class;
  protected $metadataDataType = '';
  /**
   * @var string
   */
  public $minCpuPlatform;
  /**
   * @var string
   */
  public $name;
  protected $networkInterfacesType = NetworkInterface::class;
  protected $networkInterfacesDataType = 'array';
  protected $networkPerformanceConfigType = NetworkPerformanceConfig::class;
  protected $networkPerformanceConfigDataType = '';
  protected $paramsType = InstanceParams::class;
  protected $paramsDataType = '';
  /**
   * @var string
   */
  public $privateIpv6GoogleAccess;
  protected $reservationAffinityType = AllocationAffinity::class;
  protected $reservationAffinityDataType = '';
  /**
   * @var string[]
   */
  public $resourcePolicies;
  protected $schedulingType = Scheduling::class;
  protected $schedulingDataType = '';
  protected $serviceAccountsType = ServiceAccount::class;
  protected $serviceAccountsDataType = 'array';
  protected $tagsType = Tags::class;
  protected $tagsDataType = '';

  /**
   * @param AdvancedMachineFeatures
   */
  public function setAdvancedMachineFeatures(AdvancedMachineFeatures $advancedMachineFeatures)
  {
    $this->advancedMachineFeatures = $advancedMachineFeatures;
  }
  /**
   * @return AdvancedMachineFeatures
   */
  public function getAdvancedMachineFeatures()
  {
    return $this->advancedMachineFeatures;
  }
  /**
   * @param bool
   */
  public function setCanIpForward($canIpForward)
  {
    $this->canIpForward = $canIpForward;
  }
  /**
   * @return bool
   */
  public function getCanIpForward()
  {
    return $this->canIpForward;
  }
  /**
   * @param ConfidentialInstanceConfig
   */
  public function setConfidentialInstanceConfig(ConfidentialInstanceConfig $confidentialInstanceConfig)
  {
    $this->confidentialInstanceConfig = $confidentialInstanceConfig;
  }
  /**
   * @return ConfidentialInstanceConfig
   */
  public function getConfidentialInstanceConfig()
  {
    return $this->confidentialInstanceConfig;
  }
  /**
   * @param bool
   */
  public function setDeletionProtection($deletionProtection)
  {
    $this->deletionProtection = $deletionProtection;
  }
  /**
   * @return bool
   */
  public function getDeletionProtection()
  {
    return $this->deletionProtection;
  }
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
   * @param AttachedDisk[]
   */
  public function setDisks($disks)
  {
    $this->disks = $disks;
  }
  /**
   * @return AttachedDisk[]
   */
  public function getDisks()
  {
    return $this->disks;
  }
  /**
   * @param DisplayDevice
   */
  public function setDisplayDevice(DisplayDevice $displayDevice)
  {
    $this->displayDevice = $displayDevice;
  }
  /**
   * @return DisplayDevice
   */
  public function getDisplayDevice()
  {
    return $this->displayDevice;
  }
  /**
   * @param AcceleratorConfig[]
   */
  public function setGuestAccelerators($guestAccelerators)
  {
    $this->guestAccelerators = $guestAccelerators;
  }
  /**
   * @return AcceleratorConfig[]
   */
  public function getGuestAccelerators()
  {
    return $this->guestAccelerators;
  }
  /**
   * @param string
   */
  public function setHostname($hostname)
  {
    $this->hostname = $hostname;
  }
  /**
   * @return string
   */
  public function getHostname()
  {
    return $this->hostname;
  }
  /**
   * @param CustomerEncryptionKey
   */
  public function setInstanceEncryptionKey(CustomerEncryptionKey $instanceEncryptionKey)
  {
    $this->instanceEncryptionKey = $instanceEncryptionKey;
  }
  /**
   * @return CustomerEncryptionKey
   */
  public function getInstanceEncryptionKey()
  {
    return $this->instanceEncryptionKey;
  }
  /**
   * @param string
   */
  public function setKeyRevocationActionType($keyRevocationActionType)
  {
    $this->keyRevocationActionType = $keyRevocationActionType;
  }
  /**
   * @return string
   */
  public function getKeyRevocationActionType()
  {
    return $this->keyRevocationActionType;
  }
  /**
   * @param string[]
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
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
   * @param Metadata
   */
  public function setMetadata(Metadata $metadata)
  {
    $this->metadata = $metadata;
  }
  /**
   * @return Metadata
   */
  public function getMetadata()
  {
    return $this->metadata;
  }
  /**
   * @param string
   */
  public function setMinCpuPlatform($minCpuPlatform)
  {
    $this->minCpuPlatform = $minCpuPlatform;
  }
  /**
   * @return string
   */
  public function getMinCpuPlatform()
  {
    return $this->minCpuPlatform;
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
   * @param NetworkInterface[]
   */
  public function setNetworkInterfaces($networkInterfaces)
  {
    $this->networkInterfaces = $networkInterfaces;
  }
  /**
   * @return NetworkInterface[]
   */
  public function getNetworkInterfaces()
  {
    return $this->networkInterfaces;
  }
  /**
   * @param NetworkPerformanceConfig
   */
  public function setNetworkPerformanceConfig(NetworkPerformanceConfig $networkPerformanceConfig)
  {
    $this->networkPerformanceConfig = $networkPerformanceConfig;
  }
  /**
   * @return NetworkPerformanceConfig
   */
  public function getNetworkPerformanceConfig()
  {
    return $this->networkPerformanceConfig;
  }
  /**
   * @param InstanceParams
   */
  public function setParams(InstanceParams $params)
  {
    $this->params = $params;
  }
  /**
   * @return InstanceParams
   */
  public function getParams()
  {
    return $this->params;
  }
  /**
   * @param string
   */
  public function setPrivateIpv6GoogleAccess($privateIpv6GoogleAccess)
  {
    $this->privateIpv6GoogleAccess = $privateIpv6GoogleAccess;
  }
  /**
   * @return string
   */
  public function getPrivateIpv6GoogleAccess()
  {
    return $this->privateIpv6GoogleAccess;
  }
  /**
   * @param AllocationAffinity
   */
  public function setReservationAffinity(AllocationAffinity $reservationAffinity)
  {
    $this->reservationAffinity = $reservationAffinity;
  }
  /**
   * @return AllocationAffinity
   */
  public function getReservationAffinity()
  {
    return $this->reservationAffinity;
  }
  /**
   * @param string[]
   */
  public function setResourcePolicies($resourcePolicies)
  {
    $this->resourcePolicies = $resourcePolicies;
  }
  /**
   * @return string[]
   */
  public function getResourcePolicies()
  {
    return $this->resourcePolicies;
  }
  /**
   * @param Scheduling
   */
  public function setScheduling(Scheduling $scheduling)
  {
    $this->scheduling = $scheduling;
  }
  /**
   * @return Scheduling
   */
  public function getScheduling()
  {
    return $this->scheduling;
  }
  /**
   * @param ServiceAccount[]
   */
  public function setServiceAccounts($serviceAccounts)
  {
    $this->serviceAccounts = $serviceAccounts;
  }
  /**
   * @return ServiceAccount[]
   */
  public function getServiceAccounts()
  {
    return $this->serviceAccounts;
  }
  /**
   * @param Tags
   */
  public function setTags(Tags $tags)
  {
    $this->tags = $tags;
  }
  /**
   * @return Tags
   */
  public function getTags()
  {
    return $this->tags;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ComputeInstanceRestoreProperties::class, 'Google_Service_Backupdr_ComputeInstanceRestoreProperties');
