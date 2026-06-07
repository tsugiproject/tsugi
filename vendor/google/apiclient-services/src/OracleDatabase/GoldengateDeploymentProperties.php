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

namespace Google\Service\OracleDatabase;

class GoldengateDeploymentProperties extends \Google\Collection
{
  /**
   * The category is unspecified.
   */
  public const CATEGORY_GOLDENGATE_DEPLOYMENT_CATEGORY_UNSPECIFIED = 'GOLDENGATE_DEPLOYMENT_CATEGORY_UNSPECIFIED';
  /**
   * The deployment is data replication.
   */
  public const CATEGORY_DATA_REPLICATION = 'DATA_REPLICATION';
  /**
   * The deployment is data transforms.
   */
  public const CATEGORY_DATA_TRANSFORMS = 'DATA_TRANSFORMS';
  /**
   * The deployment role type is unspecified.
   */
  public const DEPLOYMENT_ROLE_GOLDENGATE_DEPLOYMENT_ROLE_TYPE_UNSPECIFIED = 'GOLDENGATE_DEPLOYMENT_ROLE_TYPE_UNSPECIFIED';
  /**
   * The deployment role type is primary.
   */
  public const DEPLOYMENT_ROLE_PRIMARY = 'PRIMARY';
  /**
   * The deployment role type is standby.
   */
  public const DEPLOYMENT_ROLE_STANDBY = 'STANDBY';
  /**
   * The license model is unspecified.
   */
  public const LICENSE_MODEL_LICENSE_MODEL_UNSPECIFIED = 'LICENSE_MODEL_UNSPECIFIED';
  /**
   * The license model is included.
   */
  public const LICENSE_MODEL_LICENSE_INCLUDED = 'LICENSE_INCLUDED';
  /**
   * The license model is bring your own license.
   */
  public const LICENSE_MODEL_BRING_YOUR_OWN_LICENSE = 'BRING_YOUR_OWN_LICENSE';
  /**
   * Default unspecified value.
   */
  public const LIFECYCLE_STATE_GOLDENGATE_DEPLOYMENT_LIFECYCLE_STATE_UNSPECIFIED = 'GOLDENGATE_DEPLOYMENT_LIFECYCLE_STATE_UNSPECIFIED';
  /**
   * The deployment is being created.
   */
  public const LIFECYCLE_STATE_CREATING = 'CREATING';
  /**
   * The deployment is being updated.
   */
  public const LIFECYCLE_STATE_UPDATING = 'UPDATING';
  /**
   * The deployment is active.
   */
  public const LIFECYCLE_STATE_ACTIVE = 'ACTIVE';
  /**
   * The deployment is inactive.
   */
  public const LIFECYCLE_STATE_INACTIVE = 'INACTIVE';
  /**
   * The deployment is being deleted.
   */
  public const LIFECYCLE_STATE_DELETING = 'DELETING';
  /**
   * The deployment is deleted.
   */
  public const LIFECYCLE_STATE_DELETED = 'DELETED';
  /**
   * The deployment failed.
   */
  public const LIFECYCLE_STATE_FAILED = 'FAILED';
  /**
   * The deployment needs attention.
   */
  public const LIFECYCLE_STATE_NEEDS_ATTENTION = 'NEEDS_ATTENTION';
  /**
   * The deployment is in progress.
   */
  public const LIFECYCLE_STATE_IN_PROGRESS = 'IN_PROGRESS';
  /**
   * The deployment is canceling.
   */
  public const LIFECYCLE_STATE_CANCELLING = 'CANCELLING';
  /**
   * The deployment is canceled.
   */
  public const LIFECYCLE_STATE_CANCELLED = 'CANCELLED';
  /**
   * The deployment succeeded.
   */
  public const LIFECYCLE_STATE_SUCCEEDED = 'SUCCEEDED';
  /**
   * The deployment is waiting.
   */
  public const LIFECYCLE_STATE_WAITING = 'WAITING';
  /**
   * The lifecycle sub-state is unspecified.
   */
  public const LIFECYCLE_SUB_STATE_GOLDENGATE_DEPLOYMENT_LIFECYCLE_SUB_STATE_UNSPECIFIED = 'GOLDENGATE_DEPLOYMENT_LIFECYCLE_SUB_STATE_UNSPECIFIED';
  /**
   * The deployment is recovering.
   */
  public const LIFECYCLE_SUB_STATE_RECOVERING = 'RECOVERING';
  /**
   * The deployment is starting.
   */
  public const LIFECYCLE_SUB_STATE_STARTING = 'STARTING';
  /**
   * The deployment is stopping.
   */
  public const LIFECYCLE_SUB_STATE_STOPPING = 'STOPPING';
  /**
   * The deployment is moving.
   */
  public const LIFECYCLE_SUB_STATE_MOVING = 'MOVING';
  /**
   * The deployment is upgrading.
   */
  public const LIFECYCLE_SUB_STATE_UPGRADING = 'UPGRADING';
  /**
   * The deployment is restoring.
   */
  public const LIFECYCLE_SUB_STATE_RESTORING = 'RESTORING';
  /**
   * The deployment is backing up.
   */
  public const LIFECYCLE_SUB_STATE_BACKING_UP = 'BACKING_UP';
  /**
   * The deployment is rolling back.
   */
  public const LIFECYCLE_SUB_STATE_ROLLING_BACK = 'ROLLING_BACK';
  /**
   * The next maintenance action type is unspecified.
   */
  public const NEXT_MAINTENANCE_ACTION_TYPE_NEXT_MAINTENANCE_ACTION_TYPE_UNSPECIFIED = 'NEXT_MAINTENANCE_ACTION_TYPE_UNSPECIFIED';
  /**
   * The next maintenance action type is upgrade.
   */
  public const NEXT_MAINTENANCE_ACTION_TYPE_UPGRADE = 'UPGRADE';
  protected $collection_key = 'placements';
  protected $backupScheduleType = GoldengateBackupSchedule::class;
  protected $backupScheduleDataType = '';
  /**
   * Output only. The category of the GoldengateDeployment.
   *
   * @var string
   */
  public $category;
  /**
   * Optional. The Minimum number of OCPUs to be made available for this
   * Deployment.
   *
   * @var int
   */
  public $cpuCoreCount;
  /**
   * Output only. The deployment backup id of the GoldengateDeployment.
   *
   * @var string
   */
  public $deploymentBackupId;
  protected $deploymentDiagnosticDataType = DeploymentDiagnosticData::class;
  protected $deploymentDiagnosticDataDataType = '';
  /**
   * Output only. The deployment role of the GoldengateDeployment.
   *
   * @var string
   */
  public $deploymentRole;
  /**
   * Required. A valid Goldengate Deployment type. For a list of supported
   * types, use the `ListGoldengateDeploymentTypes` operation.
   *
   * @var string
   */
  public $deploymentType;
  /**
   * Output only. The deployment url of the GoldengateDeployment.
   *
   * @var string
   */
  public $deploymentUrl;
  /**
   * Optional. The description of the GoldengateDeployment.
   *
   * @var string
   */
  public $description;
  /**
   * Optional. The environment type of the GoldengateDeployment.
   *
   * @var string
   */
  public $environmentType;
  /**
   * Output only. The Fully Qualified Domain Name of the GoldengateDeployment.
   *
   * @var string
   */
  public $fqdn;
  /**
   * Output only. Whether the GoldengateDeployment is healthy.
   *
   * @var bool
   */
  public $healthy;
  protected $ingressIpsType = IngressIp::class;
  protected $ingressIpsDataType = 'array';
  /**
   * Optional. Indicates if auto scaling is enabled for the Deployment's CPU
   * core count.
   *
   * @var bool
   */
  public $isAutoScalingEnabled;
  /**
   * Output only. Whether the GoldengateDeployment is of the latest version.
   *
   * @var bool
   */
  public $isLatestVersion;
  /**
   * Output only. Whether the GoldengateDeployment is public.
   *
   * @var bool
   */
  public $isPublic;
  /**
   * Output only. Whether storage utilization limit is exceeded of the
   * GoldengateDeployment.
   *
   * @var bool
   */
  public $isStorageUtilizationLimitExceeded;
  /**
   * Output only. The time last backup scheduled of the GoldengateDeployment.
   *
   * @var string
   */
  public $lastBackupScheduleTime;
  /**
   * Optional. The Oracle license model that applies to a Deployment.
   *
   * @var string
   */
  public $licenseModel;
  /**
   * Output only. The lifecycle details of the GoldengateDeployment.
   *
   * @var string
   */
  public $lifecycleDetails;
  /**
   * Output only. State of the GoldengateDeployment.
   *
   * @var string
   */
  public $lifecycleState;
  /**
   * Output only. The lifecycle sub-state of the GoldengateDeployment.
   *
   * @var string
   */
  public $lifecycleSubState;
  /**
   * Output only. The load balancer id of the GoldengateDeployment.
   *
   * @var string
   */
  public $loadBalancerId;
  /**
   * Output only. The load balancer subnet id of the GoldengateDeployment.
   *
   * @var string
   */
  public $loadBalancerSubnetId;
  protected $locksType = GoldengateDeploymentLock::class;
  protected $locksDataType = 'array';
  protected $maintenanceConfigType = GoldengateMaintenanceConfig::class;
  protected $maintenanceConfigDataType = '';
  protected $maintenanceWindowType = GoldengateMaintenanceWindow::class;
  protected $maintenanceWindowDataType = '';
  /**
   * Output only. The time next backup scheduled of the GoldengateDeployment.
   *
   * @var string
   */
  public $nextBackupScheduleTime;
  /**
   * Output only. The next maintenance action type of the GoldengateDeployment.
   *
   * @var string
   */
  public $nextMaintenanceActionType;
  /**
   * Output only. The next maintenance description of the GoldengateDeployment.
   *
   * @var string
   */
  public $nextMaintenanceDescription;
  /**
   * Output only. The time of next maintenance of the GoldengateDeployment.
   *
   * @var string
   */
  public $nextMaintenanceTime;
  /**
   * Output only. The nsg ids of the GoldengateDeployment.
   *
   * @var string[]
   */
  public $nsgIds;
  /**
   * Output only. OCID of the GoldengateDeployment.
   *
   * @var string
   */
  public $ocid;
  protected $oggDataType = GoldengateOggDeployment::class;
  protected $oggDataDataType = '';
  /**
   * Output only. The time ogg version supported until of the
   * GoldengateDeployment.
   *
   * @var string
   */
  public $oggVersionSupportEndTime;
  protected $placementsType = GoldengatePlacement::class;
  protected $placementsDataType = 'array';
  /**
   * Output only. The private ip address of the GoldengateDeployment.
   *
   * @var string
   */
  public $privateIpAddress;
  /**
   * Output only. The public ip address of the GoldengateDeployment.
   *
   * @var string
   */
  public $publicIpAddress;
  /**
   * Output only. The time when the role of the GoldengateDeployment was
   * changed.
   *
   * @var string
   */
  public $roleChangeTime;
  /**
   * Output only. The storage utilization in bytes of the GoldengateDeployment.
   *
   * @var string
   */
  public $storageUtilizationBytes;
  /**
   * Output only. The time the GoldengateDeployment was updated.
   *
   * @var string
   */
  public $updateTime;
  /**
   * Output only. The time upgrade required of the GoldengateDeployment.
   *
   * @var string
   */
  public $upgradeRequiredTime;

  /**
   * Output only. The backup schedule of the GoldengateDeployment.
   *
   * @param GoldengateBackupSchedule $backupSchedule
   */
  public function setBackupSchedule(GoldengateBackupSchedule $backupSchedule)
  {
    $this->backupSchedule = $backupSchedule;
  }
  /**
   * @return GoldengateBackupSchedule
   */
  public function getBackupSchedule()
  {
    return $this->backupSchedule;
  }
  /**
   * Output only. The category of the GoldengateDeployment.
   *
   * Accepted values: GOLDENGATE_DEPLOYMENT_CATEGORY_UNSPECIFIED,
   * DATA_REPLICATION, DATA_TRANSFORMS
   *
   * @param self::CATEGORY_* $category
   */
  public function setCategory($category)
  {
    $this->category = $category;
  }
  /**
   * @return self::CATEGORY_*
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * Optional. The Minimum number of OCPUs to be made available for this
   * Deployment.
   *
   * @param int $cpuCoreCount
   */
  public function setCpuCoreCount($cpuCoreCount)
  {
    $this->cpuCoreCount = $cpuCoreCount;
  }
  /**
   * @return int
   */
  public function getCpuCoreCount()
  {
    return $this->cpuCoreCount;
  }
  /**
   * Output only. The deployment backup id of the GoldengateDeployment.
   *
   * @param string $deploymentBackupId
   */
  public function setDeploymentBackupId($deploymentBackupId)
  {
    $this->deploymentBackupId = $deploymentBackupId;
  }
  /**
   * @return string
   */
  public function getDeploymentBackupId()
  {
    return $this->deploymentBackupId;
  }
  /**
   * Output only. The deployment diagnostic data of the GoldengateDeployment.
   *
   * @param DeploymentDiagnosticData $deploymentDiagnosticData
   */
  public function setDeploymentDiagnosticData(DeploymentDiagnosticData $deploymentDiagnosticData)
  {
    $this->deploymentDiagnosticData = $deploymentDiagnosticData;
  }
  /**
   * @return DeploymentDiagnosticData
   */
  public function getDeploymentDiagnosticData()
  {
    return $this->deploymentDiagnosticData;
  }
  /**
   * Output only. The deployment role of the GoldengateDeployment.
   *
   * Accepted values: GOLDENGATE_DEPLOYMENT_ROLE_TYPE_UNSPECIFIED, PRIMARY,
   * STANDBY
   *
   * @param self::DEPLOYMENT_ROLE_* $deploymentRole
   */
  public function setDeploymentRole($deploymentRole)
  {
    $this->deploymentRole = $deploymentRole;
  }
  /**
   * @return self::DEPLOYMENT_ROLE_*
   */
  public function getDeploymentRole()
  {
    return $this->deploymentRole;
  }
  /**
   * Required. A valid Goldengate Deployment type. For a list of supported
   * types, use the `ListGoldengateDeploymentTypes` operation.
   *
   * @param string $deploymentType
   */
  public function setDeploymentType($deploymentType)
  {
    $this->deploymentType = $deploymentType;
  }
  /**
   * @return string
   */
  public function getDeploymentType()
  {
    return $this->deploymentType;
  }
  /**
   * Output only. The deployment url of the GoldengateDeployment.
   *
   * @param string $deploymentUrl
   */
  public function setDeploymentUrl($deploymentUrl)
  {
    $this->deploymentUrl = $deploymentUrl;
  }
  /**
   * @return string
   */
  public function getDeploymentUrl()
  {
    return $this->deploymentUrl;
  }
  /**
   * Optional. The description of the GoldengateDeployment.
   *
   * @param string $description
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
   * Optional. The environment type of the GoldengateDeployment.
   *
   * @param string $environmentType
   */
  public function setEnvironmentType($environmentType)
  {
    $this->environmentType = $environmentType;
  }
  /**
   * @return string
   */
  public function getEnvironmentType()
  {
    return $this->environmentType;
  }
  /**
   * Output only. The Fully Qualified Domain Name of the GoldengateDeployment.
   *
   * @param string $fqdn
   */
  public function setFqdn($fqdn)
  {
    $this->fqdn = $fqdn;
  }
  /**
   * @return string
   */
  public function getFqdn()
  {
    return $this->fqdn;
  }
  /**
   * Output only. Whether the GoldengateDeployment is healthy.
   *
   * @param bool $healthy
   */
  public function setHealthy($healthy)
  {
    $this->healthy = $healthy;
  }
  /**
   * @return bool
   */
  public function getHealthy()
  {
    return $this->healthy;
  }
  /**
   * Output only. The ingress ips of the GoldengateDeployment.
   *
   * @param IngressIp[] $ingressIps
   */
  public function setIngressIps($ingressIps)
  {
    $this->ingressIps = $ingressIps;
  }
  /**
   * @return IngressIp[]
   */
  public function getIngressIps()
  {
    return $this->ingressIps;
  }
  /**
   * Optional. Indicates if auto scaling is enabled for the Deployment's CPU
   * core count.
   *
   * @param bool $isAutoScalingEnabled
   */
  public function setIsAutoScalingEnabled($isAutoScalingEnabled)
  {
    $this->isAutoScalingEnabled = $isAutoScalingEnabled;
  }
  /**
   * @return bool
   */
  public function getIsAutoScalingEnabled()
  {
    return $this->isAutoScalingEnabled;
  }
  /**
   * Output only. Whether the GoldengateDeployment is of the latest version.
   *
   * @param bool $isLatestVersion
   */
  public function setIsLatestVersion($isLatestVersion)
  {
    $this->isLatestVersion = $isLatestVersion;
  }
  /**
   * @return bool
   */
  public function getIsLatestVersion()
  {
    return $this->isLatestVersion;
  }
  /**
   * Output only. Whether the GoldengateDeployment is public.
   *
   * @param bool $isPublic
   */
  public function setIsPublic($isPublic)
  {
    $this->isPublic = $isPublic;
  }
  /**
   * @return bool
   */
  public function getIsPublic()
  {
    return $this->isPublic;
  }
  /**
   * Output only. Whether storage utilization limit is exceeded of the
   * GoldengateDeployment.
   *
   * @param bool $isStorageUtilizationLimitExceeded
   */
  public function setIsStorageUtilizationLimitExceeded($isStorageUtilizationLimitExceeded)
  {
    $this->isStorageUtilizationLimitExceeded = $isStorageUtilizationLimitExceeded;
  }
  /**
   * @return bool
   */
  public function getIsStorageUtilizationLimitExceeded()
  {
    return $this->isStorageUtilizationLimitExceeded;
  }
  /**
   * Output only. The time last backup scheduled of the GoldengateDeployment.
   *
   * @param string $lastBackupScheduleTime
   */
  public function setLastBackupScheduleTime($lastBackupScheduleTime)
  {
    $this->lastBackupScheduleTime = $lastBackupScheduleTime;
  }
  /**
   * @return string
   */
  public function getLastBackupScheduleTime()
  {
    return $this->lastBackupScheduleTime;
  }
  /**
   * Optional. The Oracle license model that applies to a Deployment.
   *
   * Accepted values: LICENSE_MODEL_UNSPECIFIED, LICENSE_INCLUDED,
   * BRING_YOUR_OWN_LICENSE
   *
   * @param self::LICENSE_MODEL_* $licenseModel
   */
  public function setLicenseModel($licenseModel)
  {
    $this->licenseModel = $licenseModel;
  }
  /**
   * @return self::LICENSE_MODEL_*
   */
  public function getLicenseModel()
  {
    return $this->licenseModel;
  }
  /**
   * Output only. The lifecycle details of the GoldengateDeployment.
   *
   * @param string $lifecycleDetails
   */
  public function setLifecycleDetails($lifecycleDetails)
  {
    $this->lifecycleDetails = $lifecycleDetails;
  }
  /**
   * @return string
   */
  public function getLifecycleDetails()
  {
    return $this->lifecycleDetails;
  }
  /**
   * Output only. State of the GoldengateDeployment.
   *
   * Accepted values: GOLDENGATE_DEPLOYMENT_LIFECYCLE_STATE_UNSPECIFIED,
   * CREATING, UPDATING, ACTIVE, INACTIVE, DELETING, DELETED, FAILED,
   * NEEDS_ATTENTION, IN_PROGRESS, CANCELLING, CANCELLED, SUCCEEDED, WAITING
   *
   * @param self::LIFECYCLE_STATE_* $lifecycleState
   */
  public function setLifecycleState($lifecycleState)
  {
    $this->lifecycleState = $lifecycleState;
  }
  /**
   * @return self::LIFECYCLE_STATE_*
   */
  public function getLifecycleState()
  {
    return $this->lifecycleState;
  }
  /**
   * Output only. The lifecycle sub-state of the GoldengateDeployment.
   *
   * Accepted values: GOLDENGATE_DEPLOYMENT_LIFECYCLE_SUB_STATE_UNSPECIFIED,
   * RECOVERING, STARTING, STOPPING, MOVING, UPGRADING, RESTORING, BACKING_UP,
   * ROLLING_BACK
   *
   * @param self::LIFECYCLE_SUB_STATE_* $lifecycleSubState
   */
  public function setLifecycleSubState($lifecycleSubState)
  {
    $this->lifecycleSubState = $lifecycleSubState;
  }
  /**
   * @return self::LIFECYCLE_SUB_STATE_*
   */
  public function getLifecycleSubState()
  {
    return $this->lifecycleSubState;
  }
  /**
   * Output only. The load balancer id of the GoldengateDeployment.
   *
   * @param string $loadBalancerId
   */
  public function setLoadBalancerId($loadBalancerId)
  {
    $this->loadBalancerId = $loadBalancerId;
  }
  /**
   * @return string
   */
  public function getLoadBalancerId()
  {
    return $this->loadBalancerId;
  }
  /**
   * Output only. The load balancer subnet id of the GoldengateDeployment.
   *
   * @param string $loadBalancerSubnetId
   */
  public function setLoadBalancerSubnetId($loadBalancerSubnetId)
  {
    $this->loadBalancerSubnetId = $loadBalancerSubnetId;
  }
  /**
   * @return string
   */
  public function getLoadBalancerSubnetId()
  {
    return $this->loadBalancerSubnetId;
  }
  /**
   * Output only. The locks of the GoldengateDeployment.
   *
   * @param GoldengateDeploymentLock[] $locks
   */
  public function setLocks($locks)
  {
    $this->locks = $locks;
  }
  /**
   * @return GoldengateDeploymentLock[]
   */
  public function getLocks()
  {
    return $this->locks;
  }
  /**
   * Optional. The maintenance configuration of the GoldengateDeployment.
   *
   * @param GoldengateMaintenanceConfig $maintenanceConfig
   */
  public function setMaintenanceConfig(GoldengateMaintenanceConfig $maintenanceConfig)
  {
    $this->maintenanceConfig = $maintenanceConfig;
  }
  /**
   * @return GoldengateMaintenanceConfig
   */
  public function getMaintenanceConfig()
  {
    return $this->maintenanceConfig;
  }
  /**
   * Optional. The maintenance window of the GoldengateDeployment.
   *
   * @param GoldengateMaintenanceWindow $maintenanceWindow
   */
  public function setMaintenanceWindow(GoldengateMaintenanceWindow $maintenanceWindow)
  {
    $this->maintenanceWindow = $maintenanceWindow;
  }
  /**
   * @return GoldengateMaintenanceWindow
   */
  public function getMaintenanceWindow()
  {
    return $this->maintenanceWindow;
  }
  /**
   * Output only. The time next backup scheduled of the GoldengateDeployment.
   *
   * @param string $nextBackupScheduleTime
   */
  public function setNextBackupScheduleTime($nextBackupScheduleTime)
  {
    $this->nextBackupScheduleTime = $nextBackupScheduleTime;
  }
  /**
   * @return string
   */
  public function getNextBackupScheduleTime()
  {
    return $this->nextBackupScheduleTime;
  }
  /**
   * Output only. The next maintenance action type of the GoldengateDeployment.
   *
   * Accepted values: NEXT_MAINTENANCE_ACTION_TYPE_UNSPECIFIED, UPGRADE
   *
   * @param self::NEXT_MAINTENANCE_ACTION_TYPE_* $nextMaintenanceActionType
   */
  public function setNextMaintenanceActionType($nextMaintenanceActionType)
  {
    $this->nextMaintenanceActionType = $nextMaintenanceActionType;
  }
  /**
   * @return self::NEXT_MAINTENANCE_ACTION_TYPE_*
   */
  public function getNextMaintenanceActionType()
  {
    return $this->nextMaintenanceActionType;
  }
  /**
   * Output only. The next maintenance description of the GoldengateDeployment.
   *
   * @param string $nextMaintenanceDescription
   */
  public function setNextMaintenanceDescription($nextMaintenanceDescription)
  {
    $this->nextMaintenanceDescription = $nextMaintenanceDescription;
  }
  /**
   * @return string
   */
  public function getNextMaintenanceDescription()
  {
    return $this->nextMaintenanceDescription;
  }
  /**
   * Output only. The time of next maintenance of the GoldengateDeployment.
   *
   * @param string $nextMaintenanceTime
   */
  public function setNextMaintenanceTime($nextMaintenanceTime)
  {
    $this->nextMaintenanceTime = $nextMaintenanceTime;
  }
  /**
   * @return string
   */
  public function getNextMaintenanceTime()
  {
    return $this->nextMaintenanceTime;
  }
  /**
   * Output only. The nsg ids of the GoldengateDeployment.
   *
   * @param string[] $nsgIds
   */
  public function setNsgIds($nsgIds)
  {
    $this->nsgIds = $nsgIds;
  }
  /**
   * @return string[]
   */
  public function getNsgIds()
  {
    return $this->nsgIds;
  }
  /**
   * Output only. OCID of the GoldengateDeployment.
   *
   * @param string $ocid
   */
  public function setOcid($ocid)
  {
    $this->ocid = $ocid;
  }
  /**
   * @return string
   */
  public function getOcid()
  {
    return $this->ocid;
  }
  /**
   * Required. The ogg data of the GoldengateDeployment.
   *
   * @param GoldengateOggDeployment $oggData
   */
  public function setOggData(GoldengateOggDeployment $oggData)
  {
    $this->oggData = $oggData;
  }
  /**
   * @return GoldengateOggDeployment
   */
  public function getOggData()
  {
    return $this->oggData;
  }
  /**
   * Output only. The time ogg version supported until of the
   * GoldengateDeployment.
   *
   * @param string $oggVersionSupportEndTime
   */
  public function setOggVersionSupportEndTime($oggVersionSupportEndTime)
  {
    $this->oggVersionSupportEndTime = $oggVersionSupportEndTime;
  }
  /**
   * @return string
   */
  public function getOggVersionSupportEndTime()
  {
    return $this->oggVersionSupportEndTime;
  }
  /**
   * Output only. The placements of the GoldengateDeployment.
   *
   * @param GoldengatePlacement[] $placements
   */
  public function setPlacements($placements)
  {
    $this->placements = $placements;
  }
  /**
   * @return GoldengatePlacement[]
   */
  public function getPlacements()
  {
    return $this->placements;
  }
  /**
   * Output only. The private ip address of the GoldengateDeployment.
   *
   * @param string $privateIpAddress
   */
  public function setPrivateIpAddress($privateIpAddress)
  {
    $this->privateIpAddress = $privateIpAddress;
  }
  /**
   * @return string
   */
  public function getPrivateIpAddress()
  {
    return $this->privateIpAddress;
  }
  /**
   * Output only. The public ip address of the GoldengateDeployment.
   *
   * @param string $publicIpAddress
   */
  public function setPublicIpAddress($publicIpAddress)
  {
    $this->publicIpAddress = $publicIpAddress;
  }
  /**
   * @return string
   */
  public function getPublicIpAddress()
  {
    return $this->publicIpAddress;
  }
  /**
   * Output only. The time when the role of the GoldengateDeployment was
   * changed.
   *
   * @param string $roleChangeTime
   */
  public function setRoleChangeTime($roleChangeTime)
  {
    $this->roleChangeTime = $roleChangeTime;
  }
  /**
   * @return string
   */
  public function getRoleChangeTime()
  {
    return $this->roleChangeTime;
  }
  /**
   * Output only. The storage utilization in bytes of the GoldengateDeployment.
   *
   * @param string $storageUtilizationBytes
   */
  public function setStorageUtilizationBytes($storageUtilizationBytes)
  {
    $this->storageUtilizationBytes = $storageUtilizationBytes;
  }
  /**
   * @return string
   */
  public function getStorageUtilizationBytes()
  {
    return $this->storageUtilizationBytes;
  }
  /**
   * Output only. The time the GoldengateDeployment was updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
  /**
   * Output only. The time upgrade required of the GoldengateDeployment.
   *
   * @param string $upgradeRequiredTime
   */
  public function setUpgradeRequiredTime($upgradeRequiredTime)
  {
    $this->upgradeRequiredTime = $upgradeRequiredTime;
  }
  /**
   * @return string
   */
  public function getUpgradeRequiredTime()
  {
    return $this->upgradeRequiredTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDeploymentProperties::class, 'Google_Service_OracleDatabase_GoldengateDeploymentProperties');
