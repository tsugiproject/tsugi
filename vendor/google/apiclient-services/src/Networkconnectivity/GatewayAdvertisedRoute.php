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

namespace Google\Service\Networkconnectivity;

class GatewayAdvertisedRoute extends \Google\Model
{
  /**
   * No recipient specified. By default routes are advertised to the hub.
   */
  public const RECIPIENT_RECIPIENT_UNSPECIFIED = 'RECIPIENT_UNSPECIFIED';
  /**
   * Advertises a route toward the hub. Other spokes reachable from this spoke
   * will receive the route.
   */
  public const RECIPIENT_ADVERTISE_TO_HUB = 'ADVERTISE_TO_HUB';
  /**
   * No state information available
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The resource's create operation is in progress.
   */
  public const STATE_CREATING = 'CREATING';
  /**
   * The resource is active
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * The resource's delete operation is in progress.
   */
  public const STATE_DELETING = 'DELETING';
  /**
   * The resource's accept operation is in progress.
   */
  public const STATE_ACCEPTING = 'ACCEPTING';
  /**
   * The resource's reject operation is in progress.
   */
  public const STATE_REJECTING = 'REJECTING';
  /**
   * The resource's update operation is in progress.
   */
  public const STATE_UPDATING = 'UPDATING';
  /**
   * The resource is inactive.
   */
  public const STATE_INACTIVE = 'INACTIVE';
  /**
   * The hub associated with this spoke resource has been deleted. This state
   * applies to spoke resources only.
   */
  public const STATE_OBSOLETE = 'OBSOLETE';
  /**
   * The resource is in an undefined state due to resource creation or deletion
   * failure. You can try to delete the resource later or contact support for
   * help.
   */
  public const STATE_FAILED = 'FAILED';
  /**
   * Output only. The time the gateway advertised route was created.
   *
   * @var string
   */
  public $createTime;
  /**
   * An optional description of the gateway advertised route.
   *
   * @var string
   */
  public $description;
  /**
   * Immutable. This route's advertised IP address range. Must be a valid CIDR-
   * formatted prefix. If an IP address is provided without a subnet mask, it is
   * interpreted as, for IPv4, a `/32` singular IP address range, and, for IPv6,
   * `/128`.
   *
   * @var string
   */
  public $ipRange;
  /**
   * Optional labels in key-value pair format. For more information about
   * labels, see [Requirements for labels](https://cloud.google.com/resource-
   * manager/docs/creating-managing-labels#requirements).
   *
   * @var string[]
   */
  public $labels;
  /**
   * Identifier. The name of the gateway advertised route. Route names must be
   * unique and use the following form: `projects/{project_number}/locations/{re
   * gion}/spokes/{spoke}/gatewayAdvertisedRoutes/{gateway_advertised_route_id}`
   *
   * @var string
   */
  public $name;
  /**
   * Optional. The priority of this advertised route. You can choose a value
   * from `0` to `65335`. If you don't provide a value, Google Cloud assigns a
   * priority of `100` to the ranges.
   *
   * @var int
   */
  public $priority;
  /**
   * Optional. The recipient of this advertised route.
   *
   * @var string
   */
  public $recipient;
  /**
   * Output only. The current lifecycle state of this gateway advertised route.
   *
   * @var string
   */
  public $state;
  /**
   * Output only. The Google-generated UUID for the gateway advertised route.
   * This value is unique across all gateway advertised route resources. If a
   * gateway advertised route is deleted and another with the same name is
   * created, the new route is assigned a different `unique_id`.
   *
   * @var string
   */
  public $uniqueId;
  /**
   * Output only. The time the gateway advertised route was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. The time the gateway advertised route was created.
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
   * An optional description of the gateway advertised route.
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
   * Immutable. This route's advertised IP address range. Must be a valid CIDR-
   * formatted prefix. If an IP address is provided without a subnet mask, it is
   * interpreted as, for IPv4, a `/32` singular IP address range, and, for IPv6,
   * `/128`.
   *
   * @param string $ipRange
   */
  public function setIpRange($ipRange)
  {
    $this->ipRange = $ipRange;
  }
  /**
   * @return string
   */
  public function getIpRange()
  {
    return $this->ipRange;
  }
  /**
   * Optional labels in key-value pair format. For more information about
   * labels, see [Requirements for labels](https://cloud.google.com/resource-
   * manager/docs/creating-managing-labels#requirements).
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
   * Identifier. The name of the gateway advertised route. Route names must be
   * unique and use the following form: `projects/{project_number}/locations/{re
   * gion}/spokes/{spoke}/gatewayAdvertisedRoutes/{gateway_advertised_route_id}`
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
   * Optional. The priority of this advertised route. You can choose a value
   * from `0` to `65335`. If you don't provide a value, Google Cloud assigns a
   * priority of `100` to the ranges.
   *
   * @param int $priority
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  /**
   * @return int
   */
  public function getPriority()
  {
    return $this->priority;
  }
  /**
   * Optional. The recipient of this advertised route.
   *
   * Accepted values: RECIPIENT_UNSPECIFIED, ADVERTISE_TO_HUB
   *
   * @param self::RECIPIENT_* $recipient
   */
  public function setRecipient($recipient)
  {
    $this->recipient = $recipient;
  }
  /**
   * @return self::RECIPIENT_*
   */
  public function getRecipient()
  {
    return $this->recipient;
  }
  /**
   * Output only. The current lifecycle state of this gateway advertised route.
   *
   * Accepted values: STATE_UNSPECIFIED, CREATING, ACTIVE, DELETING, ACCEPTING,
   * REJECTING, UPDATING, INACTIVE, OBSOLETE, FAILED
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * Output only. The Google-generated UUID for the gateway advertised route.
   * This value is unique across all gateway advertised route resources. If a
   * gateway advertised route is deleted and another with the same name is
   * created, the new route is assigned a different `unique_id`.
   *
   * @param string $uniqueId
   */
  public function setUniqueId($uniqueId)
  {
    $this->uniqueId = $uniqueId;
  }
  /**
   * @return string
   */
  public function getUniqueId()
  {
    return $this->uniqueId;
  }
  /**
   * Output only. The time the gateway advertised route was last updated.
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
class_alias(GatewayAdvertisedRoute::class, 'Google_Service_Networkconnectivity_GatewayAdvertisedRoute');
