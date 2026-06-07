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

class GkeNetworkPolicyInfo extends \Google\Model
{
  /**
   * Possible values: ALLOW, DENY
   *
   * @var string
   */
  public $action;
  /**
   * Possible values: INGRESS, EGRESS
   *
   * @var string
   */
  public $direction;
  /**
   * The name of the Network Policy.
   *
   * @var string
   */
  public $displayName;
  /**
   * The URI of the Network Policy. Format for a Network Policy in a zonal
   * cluster: `projects//zones//clusters//k8s/namespaces//networking.k8s.io/netw
   * orkpolicies/` Format for a Network Policy in a regional cluster: `projects/
   * /locations//clusters//k8s/namespaces//networking.k8s.io/networkpolicies/`
   *
   * @var string
   */
  public $uri;

  /**
   * Possible values: ALLOW, DENY
   *
   * @param string $action
   */
  public function setAction($action)
  {
    $this->action = $action;
  }
  /**
   * @return string
   */
  public function getAction()
  {
    return $this->action;
  }
  /**
   * Possible values: INGRESS, EGRESS
   *
   * @param string $direction
   */
  public function setDirection($direction)
  {
    $this->direction = $direction;
  }
  /**
   * @return string
   */
  public function getDirection()
  {
    return $this->direction;
  }
  /**
   * The name of the Network Policy.
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * The URI of the Network Policy. Format for a Network Policy in a zonal
   * cluster: `projects//zones//clusters//k8s/namespaces//networking.k8s.io/netw
   * orkpolicies/` Format for a Network Policy in a regional cluster: `projects/
   * /locations//clusters//k8s/namespaces//networking.k8s.io/networkpolicies/`
   *
   * @param string $uri
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GkeNetworkPolicyInfo::class, 'Google_Service_NetworkManagement_GkeNetworkPolicyInfo');
