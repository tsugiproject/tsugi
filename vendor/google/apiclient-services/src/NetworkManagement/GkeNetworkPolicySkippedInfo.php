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

class GkeNetworkPolicySkippedInfo extends \Google\Model
{
  /**
   * Unused default value.
   */
  public const REASON_REASON_UNSPECIFIED = 'REASON_UNSPECIFIED';
  /**
   * Network Policy is disabled on the cluster.
   */
  public const REASON_NETWORK_POLICY_DISABLED = 'NETWORK_POLICY_DISABLED';
  /**
   * Ingress traffic to a Pod from a source on the same Node is always allowed.
   */
  public const REASON_INGRESS_SOURCE_ON_SAME_NODE = 'INGRESS_SOURCE_ON_SAME_NODE';
  /**
   * Egress traffic from a Pod that uses the Node's network namespace is not
   * subject to Network Policy.
   */
  public const REASON_EGRESS_FROM_NODE_NETWORK_NAMESPACE_POD = 'EGRESS_FROM_NODE_NETWORK_NAMESPACE_POD';
  /**
   * Network Policy is not applied to response traffic. This is because GKE
   * Network Policy evaluation is stateful in both GKE Dataplane V2 (eBPF) and
   * legacy (iptables) implementations.
   */
  public const REASON_NETWORK_POLICY_NOT_APPLIED_TO_RESPONSE_TRAFFIC = 'NETWORK_POLICY_NOT_APPLIED_TO_RESPONSE_TRAFFIC';
  /**
   * Network Policy evaluation is currently not supported for clusters with FQDN
   * Network Policies enabled.
   */
  public const REASON_NETWORK_POLICY_ANALYSIS_UNSUPPORTED = 'NETWORK_POLICY_ANALYSIS_UNSUPPORTED';
  /**
   * Reason why Network Policy evaluation was skipped.
   *
   * @var string
   */
  public $reason;

  /**
   * Reason why Network Policy evaluation was skipped.
   *
   * Accepted values: REASON_UNSPECIFIED, NETWORK_POLICY_DISABLED,
   * INGRESS_SOURCE_ON_SAME_NODE, EGRESS_FROM_NODE_NETWORK_NAMESPACE_POD,
   * NETWORK_POLICY_NOT_APPLIED_TO_RESPONSE_TRAFFIC,
   * NETWORK_POLICY_ANALYSIS_UNSUPPORTED
   *
   * @param self::REASON_* $reason
   */
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  /**
   * @return self::REASON_*
   */
  public function getReason()
  {
    return $this->reason;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GkeNetworkPolicySkippedInfo::class, 'Google_Service_NetworkManagement_GkeNetworkPolicySkippedInfo');
