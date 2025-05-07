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

namespace Google\Service\NetworkManagement;

class Step extends \Google\Model
{
  protected $abortType = AbortInfo::class;
  protected $abortDataType = '';
  protected $appEngineVersionType = AppEngineVersionInfo::class;
  protected $appEngineVersionDataType = '';
  /**
   * @var bool
   */
  public $causesDrop;
  protected $cloudFunctionType = CloudFunctionInfo::class;
  protected $cloudFunctionDataType = '';
  protected $cloudRunRevisionType = CloudRunRevisionInfo::class;
  protected $cloudRunRevisionDataType = '';
  protected $cloudSqlInstanceType = CloudSQLInstanceInfo::class;
  protected $cloudSqlInstanceDataType = '';
  protected $deliverType = DeliverInfo::class;
  protected $deliverDataType = '';
  /**
   * @var string
   */
  public $description;
  protected $dropType = DropInfo::class;
  protected $dropDataType = '';
  protected $endpointType = EndpointInfo::class;
  protected $endpointDataType = '';
  protected $firewallType = FirewallInfo::class;
  protected $firewallDataType = '';
  protected $forwardType = ForwardInfo::class;
  protected $forwardDataType = '';
  protected $forwardingRuleType = ForwardingRuleInfo::class;
  protected $forwardingRuleDataType = '';
  protected $gkeMasterType = GKEMasterInfo::class;
  protected $gkeMasterDataType = '';
  protected $googleServiceType = GoogleServiceInfo::class;
  protected $googleServiceDataType = '';
  protected $instanceType = InstanceInfo::class;
  protected $instanceDataType = '';
  protected $loadBalancerType = LoadBalancerInfo::class;
  protected $loadBalancerDataType = '';
  protected $loadBalancerBackendInfoType = LoadBalancerBackendInfo::class;
  protected $loadBalancerBackendInfoDataType = '';
  protected $natType = NatInfo::class;
  protected $natDataType = '';
  protected $networkType = NetworkInfo::class;
  protected $networkDataType = '';
  /**
   * @var string
   */
  public $projectId;
  protected $proxyConnectionType = ProxyConnectionInfo::class;
  protected $proxyConnectionDataType = '';
  protected $redisClusterType = RedisClusterInfo::class;
  protected $redisClusterDataType = '';
  protected $redisInstanceType = RedisInstanceInfo::class;
  protected $redisInstanceDataType = '';
  protected $routeType = RouteInfo::class;
  protected $routeDataType = '';
  protected $serverlessNegType = ServerlessNegInfo::class;
  protected $serverlessNegDataType = '';
  /**
   * @var string
   */
  public $state;
  protected $storageBucketType = StorageBucketInfo::class;
  protected $storageBucketDataType = '';
  protected $vpcConnectorType = VpcConnectorInfo::class;
  protected $vpcConnectorDataType = '';
  protected $vpnGatewayType = VpnGatewayInfo::class;
  protected $vpnGatewayDataType = '';
  protected $vpnTunnelType = VpnTunnelInfo::class;
  protected $vpnTunnelDataType = '';

  /**
   * @param AbortInfo
   */
  public function setAbort(AbortInfo $abort)
  {
    $this->abort = $abort;
  }
  /**
   * @return AbortInfo
   */
  public function getAbort()
  {
    return $this->abort;
  }
  /**
   * @param AppEngineVersionInfo
   */
  public function setAppEngineVersion(AppEngineVersionInfo $appEngineVersion)
  {
    $this->appEngineVersion = $appEngineVersion;
  }
  /**
   * @return AppEngineVersionInfo
   */
  public function getAppEngineVersion()
  {
    return $this->appEngineVersion;
  }
  /**
   * @param bool
   */
  public function setCausesDrop($causesDrop)
  {
    $this->causesDrop = $causesDrop;
  }
  /**
   * @return bool
   */
  public function getCausesDrop()
  {
    return $this->causesDrop;
  }
  /**
   * @param CloudFunctionInfo
   */
  public function setCloudFunction(CloudFunctionInfo $cloudFunction)
  {
    $this->cloudFunction = $cloudFunction;
  }
  /**
   * @return CloudFunctionInfo
   */
  public function getCloudFunction()
  {
    return $this->cloudFunction;
  }
  /**
   * @param CloudRunRevisionInfo
   */
  public function setCloudRunRevision(CloudRunRevisionInfo $cloudRunRevision)
  {
    $this->cloudRunRevision = $cloudRunRevision;
  }
  /**
   * @return CloudRunRevisionInfo
   */
  public function getCloudRunRevision()
  {
    return $this->cloudRunRevision;
  }
  /**
   * @param CloudSQLInstanceInfo
   */
  public function setCloudSqlInstance(CloudSQLInstanceInfo $cloudSqlInstance)
  {
    $this->cloudSqlInstance = $cloudSqlInstance;
  }
  /**
   * @return CloudSQLInstanceInfo
   */
  public function getCloudSqlInstance()
  {
    return $this->cloudSqlInstance;
  }
  /**
   * @param DeliverInfo
   */
  public function setDeliver(DeliverInfo $deliver)
  {
    $this->deliver = $deliver;
  }
  /**
   * @return DeliverInfo
   */
  public function getDeliver()
  {
    return $this->deliver;
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
   * @param DropInfo
   */
  public function setDrop(DropInfo $drop)
  {
    $this->drop = $drop;
  }
  /**
   * @return DropInfo
   */
  public function getDrop()
  {
    return $this->drop;
  }
  /**
   * @param EndpointInfo
   */
  public function setEndpoint(EndpointInfo $endpoint)
  {
    $this->endpoint = $endpoint;
  }
  /**
   * @return EndpointInfo
   */
  public function getEndpoint()
  {
    return $this->endpoint;
  }
  /**
   * @param FirewallInfo
   */
  public function setFirewall(FirewallInfo $firewall)
  {
    $this->firewall = $firewall;
  }
  /**
   * @return FirewallInfo
   */
  public function getFirewall()
  {
    return $this->firewall;
  }
  /**
   * @param ForwardInfo
   */
  public function setForward(ForwardInfo $forward)
  {
    $this->forward = $forward;
  }
  /**
   * @return ForwardInfo
   */
  public function getForward()
  {
    return $this->forward;
  }
  /**
   * @param ForwardingRuleInfo
   */
  public function setForwardingRule(ForwardingRuleInfo $forwardingRule)
  {
    $this->forwardingRule = $forwardingRule;
  }
  /**
   * @return ForwardingRuleInfo
   */
  public function getForwardingRule()
  {
    return $this->forwardingRule;
  }
  /**
   * @param GKEMasterInfo
   */
  public function setGkeMaster(GKEMasterInfo $gkeMaster)
  {
    $this->gkeMaster = $gkeMaster;
  }
  /**
   * @return GKEMasterInfo
   */
  public function getGkeMaster()
  {
    return $this->gkeMaster;
  }
  /**
   * @param GoogleServiceInfo
   */
  public function setGoogleService(GoogleServiceInfo $googleService)
  {
    $this->googleService = $googleService;
  }
  /**
   * @return GoogleServiceInfo
   */
  public function getGoogleService()
  {
    return $this->googleService;
  }
  /**
   * @param InstanceInfo
   */
  public function setInstance(InstanceInfo $instance)
  {
    $this->instance = $instance;
  }
  /**
   * @return InstanceInfo
   */
  public function getInstance()
  {
    return $this->instance;
  }
  /**
   * @param LoadBalancerInfo
   */
  public function setLoadBalancer(LoadBalancerInfo $loadBalancer)
  {
    $this->loadBalancer = $loadBalancer;
  }
  /**
   * @return LoadBalancerInfo
   */
  public function getLoadBalancer()
  {
    return $this->loadBalancer;
  }
  /**
   * @param LoadBalancerBackendInfo
   */
  public function setLoadBalancerBackendInfo(LoadBalancerBackendInfo $loadBalancerBackendInfo)
  {
    $this->loadBalancerBackendInfo = $loadBalancerBackendInfo;
  }
  /**
   * @return LoadBalancerBackendInfo
   */
  public function getLoadBalancerBackendInfo()
  {
    return $this->loadBalancerBackendInfo;
  }
  /**
   * @param NatInfo
   */
  public function setNat(NatInfo $nat)
  {
    $this->nat = $nat;
  }
  /**
   * @return NatInfo
   */
  public function getNat()
  {
    return $this->nat;
  }
  /**
   * @param NetworkInfo
   */
  public function setNetwork(NetworkInfo $network)
  {
    $this->network = $network;
  }
  /**
   * @return NetworkInfo
   */
  public function getNetwork()
  {
    return $this->network;
  }
  /**
   * @param string
   */
  public function setProjectId($projectId)
  {
    $this->projectId = $projectId;
  }
  /**
   * @return string
   */
  public function getProjectId()
  {
    return $this->projectId;
  }
  /**
   * @param ProxyConnectionInfo
   */
  public function setProxyConnection(ProxyConnectionInfo $proxyConnection)
  {
    $this->proxyConnection = $proxyConnection;
  }
  /**
   * @return ProxyConnectionInfo
   */
  public function getProxyConnection()
  {
    return $this->proxyConnection;
  }
  /**
   * @param RedisClusterInfo
   */
  public function setRedisCluster(RedisClusterInfo $redisCluster)
  {
    $this->redisCluster = $redisCluster;
  }
  /**
   * @return RedisClusterInfo
   */
  public function getRedisCluster()
  {
    return $this->redisCluster;
  }
  /**
   * @param RedisInstanceInfo
   */
  public function setRedisInstance(RedisInstanceInfo $redisInstance)
  {
    $this->redisInstance = $redisInstance;
  }
  /**
   * @return RedisInstanceInfo
   */
  public function getRedisInstance()
  {
    return $this->redisInstance;
  }
  /**
   * @param RouteInfo
   */
  public function setRoute(RouteInfo $route)
  {
    $this->route = $route;
  }
  /**
   * @return RouteInfo
   */
  public function getRoute()
  {
    return $this->route;
  }
  /**
   * @param ServerlessNegInfo
   */
  public function setServerlessNeg(ServerlessNegInfo $serverlessNeg)
  {
    $this->serverlessNeg = $serverlessNeg;
  }
  /**
   * @return ServerlessNegInfo
   */
  public function getServerlessNeg()
  {
    return $this->serverlessNeg;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * @param StorageBucketInfo
   */
  public function setStorageBucket(StorageBucketInfo $storageBucket)
  {
    $this->storageBucket = $storageBucket;
  }
  /**
   * @return StorageBucketInfo
   */
  public function getStorageBucket()
  {
    return $this->storageBucket;
  }
  /**
   * @param VpcConnectorInfo
   */
  public function setVpcConnector(VpcConnectorInfo $vpcConnector)
  {
    $this->vpcConnector = $vpcConnector;
  }
  /**
   * @return VpcConnectorInfo
   */
  public function getVpcConnector()
  {
    return $this->vpcConnector;
  }
  /**
   * @param VpnGatewayInfo
   */
  public function setVpnGateway(VpnGatewayInfo $vpnGateway)
  {
    $this->vpnGateway = $vpnGateway;
  }
  /**
   * @return VpnGatewayInfo
   */
  public function getVpnGateway()
  {
    return $this->vpnGateway;
  }
  /**
   * @param VpnTunnelInfo
   */
  public function setVpnTunnel(VpnTunnelInfo $vpnTunnel)
  {
    $this->vpnTunnel = $vpnTunnel;
  }
  /**
   * @return VpnTunnelInfo
   */
  public function getVpnTunnel()
  {
    return $this->vpnTunnel;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Step::class, 'Google_Service_NetworkManagement_Step');
