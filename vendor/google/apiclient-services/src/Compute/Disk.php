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

namespace Google\Service\Compute;

class Disk extends \Google\Collection
{
  protected $collection_key = 'users';
  /**
   * @var string
   */
  public $accessMode;
  /**
   * @var string
   */
  public $architecture;
  protected $asyncPrimaryDiskType = DiskAsyncReplication::class;
  protected $asyncPrimaryDiskDataType = '';
  protected $asyncSecondaryDisksType = DiskAsyncReplicationList::class;
  protected $asyncSecondaryDisksDataType = 'map';
  /**
   * @var string
   */
  public $creationTimestamp;
  /**
   * @var string
   */
  public $description;
  protected $diskEncryptionKeyType = CustomerEncryptionKey::class;
  protected $diskEncryptionKeyDataType = '';
  /**
   * @var bool
   */
  public $enableConfidentialCompute;
  protected $guestOsFeaturesType = GuestOsFeature::class;
  protected $guestOsFeaturesDataType = 'array';
  /**
   * @var string
   */
  public $id;
  /**
   * @var string
   */
  public $kind;
  /**
   * @var string
   */
  public $labelFingerprint;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $lastAttachTimestamp;
  /**
   * @var string
   */
  public $lastDetachTimestamp;
  /**
   * @var string[]
   */
  public $licenseCodes;
  /**
   * @var string[]
   */
  public $licenses;
  /**
   * @var string
   */
  public $locationHint;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $options;
  protected $paramsType = DiskParams::class;
  protected $paramsDataType = '';
  /**
   * @var string
   */
  public $physicalBlockSizeBytes;
  /**
   * @var string
   */
  public $provisionedIops;
  /**
   * @var string
   */
  public $provisionedThroughput;
  /**
   * @var string
   */
  public $region;
  /**
   * @var string[]
   */
  public $replicaZones;
  /**
   * @var string[]
   */
  public $resourcePolicies;
  protected $resourceStatusType = DiskResourceStatus::class;
  protected $resourceStatusDataType = '';
  /**
   * @var bool
   */
  public $satisfiesPzi;
  /**
   * @var bool
   */
  public $satisfiesPzs;
  /**
   * @var string
   */
  public $selfLink;
  /**
   * @var string
   */
  public $sizeGb;
  /**
   * @var string
   */
  public $sourceConsistencyGroupPolicy;
  /**
   * @var string
   */
  public $sourceConsistencyGroupPolicyId;
  /**
   * @var string
   */
  public $sourceDisk;
  /**
   * @var string
   */
  public $sourceDiskId;
  /**
   * @var string
   */
  public $sourceImage;
  protected $sourceImageEncryptionKeyType = CustomerEncryptionKey::class;
  protected $sourceImageEncryptionKeyDataType = '';
  /**
   * @var string
   */
  public $sourceImageId;
  /**
   * @var string
   */
  public $sourceInstantSnapshot;
  /**
   * @var string
   */
  public $sourceInstantSnapshotId;
  /**
   * @var string
   */
  public $sourceSnapshot;
  protected $sourceSnapshotEncryptionKeyType = CustomerEncryptionKey::class;
  protected $sourceSnapshotEncryptionKeyDataType = '';
  /**
   * @var string
   */
  public $sourceSnapshotId;
  /**
   * @var string
   */
  public $sourceStorageObject;
  /**
   * @var string
   */
  public $status;
  /**
   * @var string
   */
  public $storagePool;
  /**
   * @var string
   */
  public $type;
  /**
   * @var string[]
   */
  public $users;
  /**
   * @var string
   */
  public $zone;

  /**
   * @param string
   */
  public function setAccessMode($accessMode)
  {
    $this->accessMode = $accessMode;
  }
  /**
   * @return string
   */
  public function getAccessMode()
  {
    return $this->accessMode;
  }
  /**
   * @param string
   */
  public function setArchitecture($architecture)
  {
    $this->architecture = $architecture;
  }
  /**
   * @return string
   */
  public function getArchitecture()
  {
    return $this->architecture;
  }
  /**
   * @param DiskAsyncReplication
   */
  public function setAsyncPrimaryDisk(DiskAsyncReplication $asyncPrimaryDisk)
  {
    $this->asyncPrimaryDisk = $asyncPrimaryDisk;
  }
  /**
   * @return DiskAsyncReplication
   */
  public function getAsyncPrimaryDisk()
  {
    return $this->asyncPrimaryDisk;
  }
  /**
   * @param DiskAsyncReplicationList[]
   */
  public function setAsyncSecondaryDisks($asyncSecondaryDisks)
  {
    $this->asyncSecondaryDisks = $asyncSecondaryDisks;
  }
  /**
   * @return DiskAsyncReplicationList[]
   */
  public function getAsyncSecondaryDisks()
  {
    return $this->asyncSecondaryDisks;
  }
  /**
   * @param string
   */
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  /**
   * @return string
   */
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
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
   * @param CustomerEncryptionKey
   */
  public function setDiskEncryptionKey(CustomerEncryptionKey $diskEncryptionKey)
  {
    $this->diskEncryptionKey = $diskEncryptionKey;
  }
  /**
   * @return CustomerEncryptionKey
   */
  public function getDiskEncryptionKey()
  {
    return $this->diskEncryptionKey;
  }
  /**
   * @param bool
   */
  public function setEnableConfidentialCompute($enableConfidentialCompute)
  {
    $this->enableConfidentialCompute = $enableConfidentialCompute;
  }
  /**
   * @return bool
   */
  public function getEnableConfidentialCompute()
  {
    return $this->enableConfidentialCompute;
  }
  /**
   * @param GuestOsFeature[]
   */
  public function setGuestOsFeatures($guestOsFeatures)
  {
    $this->guestOsFeatures = $guestOsFeatures;
  }
  /**
   * @return GuestOsFeature[]
   */
  public function getGuestOsFeatures()
  {
    return $this->guestOsFeatures;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * @param string
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param string
   */
  public function setLabelFingerprint($labelFingerprint)
  {
    $this->labelFingerprint = $labelFingerprint;
  }
  /**
   * @return string
   */
  public function getLabelFingerprint()
  {
    return $this->labelFingerprint;
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
  public function setLastAttachTimestamp($lastAttachTimestamp)
  {
    $this->lastAttachTimestamp = $lastAttachTimestamp;
  }
  /**
   * @return string
   */
  public function getLastAttachTimestamp()
  {
    return $this->lastAttachTimestamp;
  }
  /**
   * @param string
   */
  public function setLastDetachTimestamp($lastDetachTimestamp)
  {
    $this->lastDetachTimestamp = $lastDetachTimestamp;
  }
  /**
   * @return string
   */
  public function getLastDetachTimestamp()
  {
    return $this->lastDetachTimestamp;
  }
  /**
   * @param string[]
   */
  public function setLicenseCodes($licenseCodes)
  {
    $this->licenseCodes = $licenseCodes;
  }
  /**
   * @return string[]
   */
  public function getLicenseCodes()
  {
    return $this->licenseCodes;
  }
  /**
   * @param string[]
   */
  public function setLicenses($licenses)
  {
    $this->licenses = $licenses;
  }
  /**
   * @return string[]
   */
  public function getLicenses()
  {
    return $this->licenses;
  }
  /**
   * @param string
   */
  public function setLocationHint($locationHint)
  {
    $this->locationHint = $locationHint;
  }
  /**
   * @return string
   */
  public function getLocationHint()
  {
    return $this->locationHint;
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
  public function setOptions($options)
  {
    $this->options = $options;
  }
  /**
   * @return string
   */
  public function getOptions()
  {
    return $this->options;
  }
  /**
   * @param DiskParams
   */
  public function setParams(DiskParams $params)
  {
    $this->params = $params;
  }
  /**
   * @return DiskParams
   */
  public function getParams()
  {
    return $this->params;
  }
  /**
   * @param string
   */
  public function setPhysicalBlockSizeBytes($physicalBlockSizeBytes)
  {
    $this->physicalBlockSizeBytes = $physicalBlockSizeBytes;
  }
  /**
   * @return string
   */
  public function getPhysicalBlockSizeBytes()
  {
    return $this->physicalBlockSizeBytes;
  }
  /**
   * @param string
   */
  public function setProvisionedIops($provisionedIops)
  {
    $this->provisionedIops = $provisionedIops;
  }
  /**
   * @return string
   */
  public function getProvisionedIops()
  {
    return $this->provisionedIops;
  }
  /**
   * @param string
   */
  public function setProvisionedThroughput($provisionedThroughput)
  {
    $this->provisionedThroughput = $provisionedThroughput;
  }
  /**
   * @return string
   */
  public function getProvisionedThroughput()
  {
    return $this->provisionedThroughput;
  }
  /**
   * @param string
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
  /**
   * @param string[]
   */
  public function setReplicaZones($replicaZones)
  {
    $this->replicaZones = $replicaZones;
  }
  /**
   * @return string[]
   */
  public function getReplicaZones()
  {
    return $this->replicaZones;
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
   * @param DiskResourceStatus
   */
  public function setResourceStatus(DiskResourceStatus $resourceStatus)
  {
    $this->resourceStatus = $resourceStatus;
  }
  /**
   * @return DiskResourceStatus
   */
  public function getResourceStatus()
  {
    return $this->resourceStatus;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzi($satisfiesPzi)
  {
    $this->satisfiesPzi = $satisfiesPzi;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzi()
  {
    return $this->satisfiesPzi;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzs($satisfiesPzs)
  {
    $this->satisfiesPzs = $satisfiesPzs;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzs()
  {
    return $this->satisfiesPzs;
  }
  /**
   * @param string
   */
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  /**
   * @return string
   */
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * @param string
   */
  public function setSizeGb($sizeGb)
  {
    $this->sizeGb = $sizeGb;
  }
  /**
   * @return string
   */
  public function getSizeGb()
  {
    return $this->sizeGb;
  }
  /**
   * @param string
   */
  public function setSourceConsistencyGroupPolicy($sourceConsistencyGroupPolicy)
  {
    $this->sourceConsistencyGroupPolicy = $sourceConsistencyGroupPolicy;
  }
  /**
   * @return string
   */
  public function getSourceConsistencyGroupPolicy()
  {
    return $this->sourceConsistencyGroupPolicy;
  }
  /**
   * @param string
   */
  public function setSourceConsistencyGroupPolicyId($sourceConsistencyGroupPolicyId)
  {
    $this->sourceConsistencyGroupPolicyId = $sourceConsistencyGroupPolicyId;
  }
  /**
   * @return string
   */
  public function getSourceConsistencyGroupPolicyId()
  {
    return $this->sourceConsistencyGroupPolicyId;
  }
  /**
   * @param string
   */
  public function setSourceDisk($sourceDisk)
  {
    $this->sourceDisk = $sourceDisk;
  }
  /**
   * @return string
   */
  public function getSourceDisk()
  {
    return $this->sourceDisk;
  }
  /**
   * @param string
   */
  public function setSourceDiskId($sourceDiskId)
  {
    $this->sourceDiskId = $sourceDiskId;
  }
  /**
   * @return string
   */
  public function getSourceDiskId()
  {
    return $this->sourceDiskId;
  }
  /**
   * @param string
   */
  public function setSourceImage($sourceImage)
  {
    $this->sourceImage = $sourceImage;
  }
  /**
   * @return string
   */
  public function getSourceImage()
  {
    return $this->sourceImage;
  }
  /**
   * @param CustomerEncryptionKey
   */
  public function setSourceImageEncryptionKey(CustomerEncryptionKey $sourceImageEncryptionKey)
  {
    $this->sourceImageEncryptionKey = $sourceImageEncryptionKey;
  }
  /**
   * @return CustomerEncryptionKey
   */
  public function getSourceImageEncryptionKey()
  {
    return $this->sourceImageEncryptionKey;
  }
  /**
   * @param string
   */
  public function setSourceImageId($sourceImageId)
  {
    $this->sourceImageId = $sourceImageId;
  }
  /**
   * @return string
   */
  public function getSourceImageId()
  {
    return $this->sourceImageId;
  }
  /**
   * @param string
   */
  public function setSourceInstantSnapshot($sourceInstantSnapshot)
  {
    $this->sourceInstantSnapshot = $sourceInstantSnapshot;
  }
  /**
   * @return string
   */
  public function getSourceInstantSnapshot()
  {
    return $this->sourceInstantSnapshot;
  }
  /**
   * @param string
   */
  public function setSourceInstantSnapshotId($sourceInstantSnapshotId)
  {
    $this->sourceInstantSnapshotId = $sourceInstantSnapshotId;
  }
  /**
   * @return string
   */
  public function getSourceInstantSnapshotId()
  {
    return $this->sourceInstantSnapshotId;
  }
  /**
   * @param string
   */
  public function setSourceSnapshot($sourceSnapshot)
  {
    $this->sourceSnapshot = $sourceSnapshot;
  }
  /**
   * @return string
   */
  public function getSourceSnapshot()
  {
    return $this->sourceSnapshot;
  }
  /**
   * @param CustomerEncryptionKey
   */
  public function setSourceSnapshotEncryptionKey(CustomerEncryptionKey $sourceSnapshotEncryptionKey)
  {
    $this->sourceSnapshotEncryptionKey = $sourceSnapshotEncryptionKey;
  }
  /**
   * @return CustomerEncryptionKey
   */
  public function getSourceSnapshotEncryptionKey()
  {
    return $this->sourceSnapshotEncryptionKey;
  }
  /**
   * @param string
   */
  public function setSourceSnapshotId($sourceSnapshotId)
  {
    $this->sourceSnapshotId = $sourceSnapshotId;
  }
  /**
   * @return string
   */
  public function getSourceSnapshotId()
  {
    return $this->sourceSnapshotId;
  }
  /**
   * @param string
   */
  public function setSourceStorageObject($sourceStorageObject)
  {
    $this->sourceStorageObject = $sourceStorageObject;
  }
  /**
   * @return string
   */
  public function getSourceStorageObject()
  {
    return $this->sourceStorageObject;
  }
  /**
   * @param string
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }
  /**
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }
  /**
   * @param string
   */
  public function setStoragePool($storagePool)
  {
    $this->storagePool = $storagePool;
  }
  /**
   * @return string
   */
  public function getStoragePool()
  {
    return $this->storagePool;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
  /**
   * @param string[]
   */
  public function setUsers($users)
  {
    $this->users = $users;
  }
  /**
   * @return string[]
   */
  public function getUsers()
  {
    return $this->users;
  }
  /**
   * @param string
   */
  public function setZone($zone)
  {
    $this->zone = $zone;
  }
  /**
   * @return string
   */
  public function getZone()
  {
    return $this->zone;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Disk::class, 'Google_Service_Compute_Disk');
