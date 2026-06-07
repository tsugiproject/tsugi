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

namespace Google\Service\VMwareEngine;

class DatastoreNetwork extends \Google\Model
{
  /**
   * Optional. connection_count is used to set multiple connections from NFS
   * client on ESXi host to NFS server. A higher number of connections results
   * in better performance on datastores. In MountDatastore API by default max 4
   * connections are configured. User can set value of connection_count between
   * 1 to 4. Connection_count is supported from vsphere 8.0u1 for earlier
   * version 1 connection count is set on the ESXi hosts.
   *
   * @var int
   */
  public $connectionCount;
  /**
   * Optional. MTU value is set on the VMKernel adapter for the NFS traffic. By
   * default standard 1500 MTU size is set in MountDatastore API which is good
   * for typical setups. However google VPC networks supports jumbo MTU 8896. We
   * recommend to tune this value based on the NFS traffic performance.
   * Performance can be determined using benchmarking I/O tools like fio
   * (Flexible I/O Tester) utility.
   *
   * @var int
   */
  public $mtu;
  /**
   * Output only. The resource name of the network peering, used to access the
   * file share by clients on private cloud. Resource names are schemeless URIs
   * that follow the conventions in
   * https://cloud.google.com/apis/design/resource_names. e.g. projects/my-
   * project/locations/us-central1/networkPeerings/my-network-peering
   *
   * @var string
   */
  public $networkPeering;
  /**
   * Required. The resource name of the subnet Resource names are schemeless
   * URIs that follow the conventions in
   * https://cloud.google.com/apis/design/resource_names. e.g. projects/my-
   * project/locations/us-central1/subnets/my-subnet
   *
   * @var string
   */
  public $subnet;

  /**
   * Optional. connection_count is used to set multiple connections from NFS
   * client on ESXi host to NFS server. A higher number of connections results
   * in better performance on datastores. In MountDatastore API by default max 4
   * connections are configured. User can set value of connection_count between
   * 1 to 4. Connection_count is supported from vsphere 8.0u1 for earlier
   * version 1 connection count is set on the ESXi hosts.
   *
   * @param int $connectionCount
   */
  public function setConnectionCount($connectionCount)
  {
    $this->connectionCount = $connectionCount;
  }
  /**
   * @return int
   */
  public function getConnectionCount()
  {
    return $this->connectionCount;
  }
  /**
   * Optional. MTU value is set on the VMKernel adapter for the NFS traffic. By
   * default standard 1500 MTU size is set in MountDatastore API which is good
   * for typical setups. However google VPC networks supports jumbo MTU 8896. We
   * recommend to tune this value based on the NFS traffic performance.
   * Performance can be determined using benchmarking I/O tools like fio
   * (Flexible I/O Tester) utility.
   *
   * @param int $mtu
   */
  public function setMtu($mtu)
  {
    $this->mtu = $mtu;
  }
  /**
   * @return int
   */
  public function getMtu()
  {
    return $this->mtu;
  }
  /**
   * Output only. The resource name of the network peering, used to access the
   * file share by clients on private cloud. Resource names are schemeless URIs
   * that follow the conventions in
   * https://cloud.google.com/apis/design/resource_names. e.g. projects/my-
   * project/locations/us-central1/networkPeerings/my-network-peering
   *
   * @param string $networkPeering
   */
  public function setNetworkPeering($networkPeering)
  {
    $this->networkPeering = $networkPeering;
  }
  /**
   * @return string
   */
  public function getNetworkPeering()
  {
    return $this->networkPeering;
  }
  /**
   * Required. The resource name of the subnet Resource names are schemeless
   * URIs that follow the conventions in
   * https://cloud.google.com/apis/design/resource_names. e.g. projects/my-
   * project/locations/us-central1/subnets/my-subnet
   *
   * @param string $subnet
   */
  public function setSubnet($subnet)
  {
    $this->subnet = $subnet;
  }
  /**
   * @return string
   */
  public function getSubnet()
  {
    return $this->subnet;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DatastoreNetwork::class, 'Google_Service_VMwareEngine_DatastoreNetwork');
