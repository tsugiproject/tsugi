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

namespace Google\Service\Dns;

class Quota extends \Google\Collection
{
  protected $collection_key = 'whitelistedKeySpecs';
  /**
   * @var int
   */
  public $dnsKeysPerManagedZone;
  /**
   * @var int
   */
  public $gkeClustersPerManagedZone;
  /**
   * @var int
   */
  public $gkeClustersPerPolicy;
  /**
   * @var int
   */
  public $gkeClustersPerResponsePolicy;
  /**
   * @var int
   */
  public $internetHealthChecksPerManagedZone;
  /**
   * @var int
   */
  public $itemsPerRoutingPolicy;
  /**
   * @var string
   */
  public $kind;
  /**
   * @var int
   */
  public $managedZones;
  /**
   * @var int
   */
  public $managedZonesPerGkeCluster;
  /**
   * @var int
   */
  public $managedZonesPerNetwork;
  /**
   * @var int
   */
  public $nameserversPerDelegation;
  /**
   * @var int
   */
  public $networksPerManagedZone;
  /**
   * @var int
   */
  public $networksPerPolicy;
  /**
   * @var int
   */
  public $networksPerResponsePolicy;
  /**
   * @var int
   */
  public $peeringZonesPerTargetNetwork;
  /**
   * @var int
   */
  public $policies;
  /**
   * @var int
   */
  public $resourceRecordsPerRrset;
  /**
   * @var int
   */
  public $responsePolicies;
  /**
   * @var int
   */
  public $responsePolicyRulesPerResponsePolicy;
  /**
   * @var int
   */
  public $rrsetAdditionsPerChange;
  /**
   * @var int
   */
  public $rrsetDeletionsPerChange;
  /**
   * @var int
   */
  public $rrsetsPerManagedZone;
  /**
   * @var int
   */
  public $targetNameServersPerManagedZone;
  /**
   * @var int
   */
  public $targetNameServersPerPolicy;
  /**
   * @var int
   */
  public $totalRrdataSizePerChange;
  protected $whitelistedKeySpecsType = DnsKeySpec::class;
  protected $whitelistedKeySpecsDataType = 'array';

  /**
   * @param int
   */
  public function setDnsKeysPerManagedZone($dnsKeysPerManagedZone)
  {
    $this->dnsKeysPerManagedZone = $dnsKeysPerManagedZone;
  }
  /**
   * @return int
   */
  public function getDnsKeysPerManagedZone()
  {
    return $this->dnsKeysPerManagedZone;
  }
  /**
   * @param int
   */
  public function setGkeClustersPerManagedZone($gkeClustersPerManagedZone)
  {
    $this->gkeClustersPerManagedZone = $gkeClustersPerManagedZone;
  }
  /**
   * @return int
   */
  public function getGkeClustersPerManagedZone()
  {
    return $this->gkeClustersPerManagedZone;
  }
  /**
   * @param int
   */
  public function setGkeClustersPerPolicy($gkeClustersPerPolicy)
  {
    $this->gkeClustersPerPolicy = $gkeClustersPerPolicy;
  }
  /**
   * @return int
   */
  public function getGkeClustersPerPolicy()
  {
    return $this->gkeClustersPerPolicy;
  }
  /**
   * @param int
   */
  public function setGkeClustersPerResponsePolicy($gkeClustersPerResponsePolicy)
  {
    $this->gkeClustersPerResponsePolicy = $gkeClustersPerResponsePolicy;
  }
  /**
   * @return int
   */
  public function getGkeClustersPerResponsePolicy()
  {
    return $this->gkeClustersPerResponsePolicy;
  }
  /**
   * @param int
   */
  public function setInternetHealthChecksPerManagedZone($internetHealthChecksPerManagedZone)
  {
    $this->internetHealthChecksPerManagedZone = $internetHealthChecksPerManagedZone;
  }
  /**
   * @return int
   */
  public function getInternetHealthChecksPerManagedZone()
  {
    return $this->internetHealthChecksPerManagedZone;
  }
  /**
   * @param int
   */
  public function setItemsPerRoutingPolicy($itemsPerRoutingPolicy)
  {
    $this->itemsPerRoutingPolicy = $itemsPerRoutingPolicy;
  }
  /**
   * @return int
   */
  public function getItemsPerRoutingPolicy()
  {
    return $this->itemsPerRoutingPolicy;
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
   * @param int
   */
  public function setManagedZones($managedZones)
  {
    $this->managedZones = $managedZones;
  }
  /**
   * @return int
   */
  public function getManagedZones()
  {
    return $this->managedZones;
  }
  /**
   * @param int
   */
  public function setManagedZonesPerGkeCluster($managedZonesPerGkeCluster)
  {
    $this->managedZonesPerGkeCluster = $managedZonesPerGkeCluster;
  }
  /**
   * @return int
   */
  public function getManagedZonesPerGkeCluster()
  {
    return $this->managedZonesPerGkeCluster;
  }
  /**
   * @param int
   */
  public function setManagedZonesPerNetwork($managedZonesPerNetwork)
  {
    $this->managedZonesPerNetwork = $managedZonesPerNetwork;
  }
  /**
   * @return int
   */
  public function getManagedZonesPerNetwork()
  {
    return $this->managedZonesPerNetwork;
  }
  /**
   * @param int
   */
  public function setNameserversPerDelegation($nameserversPerDelegation)
  {
    $this->nameserversPerDelegation = $nameserversPerDelegation;
  }
  /**
   * @return int
   */
  public function getNameserversPerDelegation()
  {
    return $this->nameserversPerDelegation;
  }
  /**
   * @param int
   */
  public function setNetworksPerManagedZone($networksPerManagedZone)
  {
    $this->networksPerManagedZone = $networksPerManagedZone;
  }
  /**
   * @return int
   */
  public function getNetworksPerManagedZone()
  {
    return $this->networksPerManagedZone;
  }
  /**
   * @param int
   */
  public function setNetworksPerPolicy($networksPerPolicy)
  {
    $this->networksPerPolicy = $networksPerPolicy;
  }
  /**
   * @return int
   */
  public function getNetworksPerPolicy()
  {
    return $this->networksPerPolicy;
  }
  /**
   * @param int
   */
  public function setNetworksPerResponsePolicy($networksPerResponsePolicy)
  {
    $this->networksPerResponsePolicy = $networksPerResponsePolicy;
  }
  /**
   * @return int
   */
  public function getNetworksPerResponsePolicy()
  {
    return $this->networksPerResponsePolicy;
  }
  /**
   * @param int
   */
  public function setPeeringZonesPerTargetNetwork($peeringZonesPerTargetNetwork)
  {
    $this->peeringZonesPerTargetNetwork = $peeringZonesPerTargetNetwork;
  }
  /**
   * @return int
   */
  public function getPeeringZonesPerTargetNetwork()
  {
    return $this->peeringZonesPerTargetNetwork;
  }
  /**
   * @param int
   */
  public function setPolicies($policies)
  {
    $this->policies = $policies;
  }
  /**
   * @return int
   */
  public function getPolicies()
  {
    return $this->policies;
  }
  /**
   * @param int
   */
  public function setResourceRecordsPerRrset($resourceRecordsPerRrset)
  {
    $this->resourceRecordsPerRrset = $resourceRecordsPerRrset;
  }
  /**
   * @return int
   */
  public function getResourceRecordsPerRrset()
  {
    return $this->resourceRecordsPerRrset;
  }
  /**
   * @param int
   */
  public function setResponsePolicies($responsePolicies)
  {
    $this->responsePolicies = $responsePolicies;
  }
  /**
   * @return int
   */
  public function getResponsePolicies()
  {
    return $this->responsePolicies;
  }
  /**
   * @param int
   */
  public function setResponsePolicyRulesPerResponsePolicy($responsePolicyRulesPerResponsePolicy)
  {
    $this->responsePolicyRulesPerResponsePolicy = $responsePolicyRulesPerResponsePolicy;
  }
  /**
   * @return int
   */
  public function getResponsePolicyRulesPerResponsePolicy()
  {
    return $this->responsePolicyRulesPerResponsePolicy;
  }
  /**
   * @param int
   */
  public function setRrsetAdditionsPerChange($rrsetAdditionsPerChange)
  {
    $this->rrsetAdditionsPerChange = $rrsetAdditionsPerChange;
  }
  /**
   * @return int
   */
  public function getRrsetAdditionsPerChange()
  {
    return $this->rrsetAdditionsPerChange;
  }
  /**
   * @param int
   */
  public function setRrsetDeletionsPerChange($rrsetDeletionsPerChange)
  {
    $this->rrsetDeletionsPerChange = $rrsetDeletionsPerChange;
  }
  /**
   * @return int
   */
  public function getRrsetDeletionsPerChange()
  {
    return $this->rrsetDeletionsPerChange;
  }
  /**
   * @param int
   */
  public function setRrsetsPerManagedZone($rrsetsPerManagedZone)
  {
    $this->rrsetsPerManagedZone = $rrsetsPerManagedZone;
  }
  /**
   * @return int
   */
  public function getRrsetsPerManagedZone()
  {
    return $this->rrsetsPerManagedZone;
  }
  /**
   * @param int
   */
  public function setTargetNameServersPerManagedZone($targetNameServersPerManagedZone)
  {
    $this->targetNameServersPerManagedZone = $targetNameServersPerManagedZone;
  }
  /**
   * @return int
   */
  public function getTargetNameServersPerManagedZone()
  {
    return $this->targetNameServersPerManagedZone;
  }
  /**
   * @param int
   */
  public function setTargetNameServersPerPolicy($targetNameServersPerPolicy)
  {
    $this->targetNameServersPerPolicy = $targetNameServersPerPolicy;
  }
  /**
   * @return int
   */
  public function getTargetNameServersPerPolicy()
  {
    return $this->targetNameServersPerPolicy;
  }
  /**
   * @param int
   */
  public function setTotalRrdataSizePerChange($totalRrdataSizePerChange)
  {
    $this->totalRrdataSizePerChange = $totalRrdataSizePerChange;
  }
  /**
   * @return int
   */
  public function getTotalRrdataSizePerChange()
  {
    return $this->totalRrdataSizePerChange;
  }
  /**
   * @param DnsKeySpec[]
   */
  public function setWhitelistedKeySpecs($whitelistedKeySpecs)
  {
    $this->whitelistedKeySpecs = $whitelistedKeySpecs;
  }
  /**
   * @return DnsKeySpec[]
   */
  public function getWhitelistedKeySpecs()
  {
    return $this->whitelistedKeySpecs;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Quota::class, 'Google_Service_Dns_Quota');
