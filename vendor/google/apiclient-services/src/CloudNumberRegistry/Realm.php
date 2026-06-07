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

namespace Google\Service\CloudNumberRegistry;

class Realm extends \Google\Model
{
  /**
   * Unspecified IP version.
   */
  public const IP_VERSION_IP_VERSION_UNSPECIFIED = 'IP_VERSION_UNSPECIFIED';
  /**
   * IPv4 IP version.
   */
  public const IP_VERSION_IPV4 = 'IPV4';
  /**
   * IPv6 IP version.
   */
  public const IP_VERSION_IPV6 = 'IPV6';
  /**
   * Unspecified management type.
   */
  public const MANAGEMENT_TYPE_MANAGEMENT_TYPE_UNSPECIFIED = 'MANAGEMENT_TYPE_UNSPECIFIED';
  /**
   * Managed by Cloud Number Registry.
   */
  public const MANAGEMENT_TYPE_CNR = 'CNR';
  /**
   * Managed by User.
   */
  public const MANAGEMENT_TYPE_USER = 'USER';
  /**
   * Unspecified traffic type.
   */
  public const TRAFFIC_TYPE_TRAFFIC_TYPE_UNSPECIFIED = 'TRAFFIC_TYPE_UNSPECIFIED';
  /**
   * Unset traffic type.
   */
  public const TRAFFIC_TYPE_UNSET = 'UNSET';
  /**
   * Internet traffic.
   */
  public const TRAFFIC_TYPE_INTERNET = 'INTERNET';
  /**
   * Private traffic.
   */
  public const TRAFFIC_TYPE_PRIVATE = 'PRIVATE';
  /**
   * Linklocal traffic.
   */
  public const TRAFFIC_TYPE_LINKLOCAL = 'LINKLOCAL';
  protected $aggregatedDataType = RealmAggregatedData::class;
  protected $aggregatedDataDataType = '';
  /**
   * Output only. [Output only] Create time stamp
   *
   * @var string
   */
  public $createTime;
  protected $discoveryMetadataType = DiscoveryMetadata::class;
  protected $discoveryMetadataDataType = '';
  /**
   * Optional. IP version of the realm.
   *
   * @var string
   */
  public $ipVersion;
  /**
   * Optional. Labels as key value pairs
   *
   * @var string[]
   */
  public $labels;
  /**
   * Required. Management type of realm.
   *
   * @var string
   */
  public $managementType;
  /**
   * Required. Identifier. Unique name/ID of the realm
   *
   * @var string
   */
  public $name;
  /**
   * Required. URI of the registry book that claims the realm.
   *
   * @var string
   */
  public $registryBook;
  /**
   * Required. Traffic type of realm.
   *
   * @var string
   */
  public $trafficType;
  /**
   * Output only. [Output only] Update time stamp
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. Aggregated data for the Realm. Populated only when the view is
   * AGGREGATE.
   *
   * @param RealmAggregatedData $aggregatedData
   */
  public function setAggregatedData(RealmAggregatedData $aggregatedData)
  {
    $this->aggregatedData = $aggregatedData;
  }
  /**
   * @return RealmAggregatedData
   */
  public function getAggregatedData()
  {
    return $this->aggregatedData;
  }
  /**
   * Output only. [Output only] Create time stamp
   *
   * @param string $createTime
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
   * Output only. Discovery metadata of the realm.
   *
   * @param DiscoveryMetadata $discoveryMetadata
   */
  public function setDiscoveryMetadata(DiscoveryMetadata $discoveryMetadata)
  {
    $this->discoveryMetadata = $discoveryMetadata;
  }
  /**
   * @return DiscoveryMetadata
   */
  public function getDiscoveryMetadata()
  {
    return $this->discoveryMetadata;
  }
  /**
   * Optional. IP version of the realm.
   *
   * Accepted values: IP_VERSION_UNSPECIFIED, IPV4, IPV6
   *
   * @param self::IP_VERSION_* $ipVersion
   */
  public function setIpVersion($ipVersion)
  {
    $this->ipVersion = $ipVersion;
  }
  /**
   * @return self::IP_VERSION_*
   */
  public function getIpVersion()
  {
    return $this->ipVersion;
  }
  /**
   * Optional. Labels as key value pairs
   *
   * @param string[] $labels
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
   * Required. Management type of realm.
   *
   * Accepted values: MANAGEMENT_TYPE_UNSPECIFIED, CNR, USER
   *
   * @param self::MANAGEMENT_TYPE_* $managementType
   */
  public function setManagementType($managementType)
  {
    $this->managementType = $managementType;
  }
  /**
   * @return self::MANAGEMENT_TYPE_*
   */
  public function getManagementType()
  {
    return $this->managementType;
  }
  /**
   * Required. Identifier. Unique name/ID of the realm
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
   * Required. URI of the registry book that claims the realm.
   *
   * @param string $registryBook
   */
  public function setRegistryBook($registryBook)
  {
    $this->registryBook = $registryBook;
  }
  /**
   * @return string
   */
  public function getRegistryBook()
  {
    return $this->registryBook;
  }
  /**
   * Required. Traffic type of realm.
   *
   * Accepted values: TRAFFIC_TYPE_UNSPECIFIED, UNSET, INTERNET, PRIVATE,
   * LINKLOCAL
   *
   * @param self::TRAFFIC_TYPE_* $trafficType
   */
  public function setTrafficType($trafficType)
  {
    $this->trafficType = $trafficType;
  }
  /**
   * @return self::TRAFFIC_TYPE_*
   */
  public function getTrafficType()
  {
    return $this->trafficType;
  }
  /**
   * Output only. [Output only] Update time stamp
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Realm::class, 'Google_Service_CloudNumberRegistry_Realm');
