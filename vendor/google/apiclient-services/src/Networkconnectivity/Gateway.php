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

class Gateway extends \Google\Collection
{
  /**
   * The gateway capacity is unspecified.
   */
  public const CAPACITY_GATEWAY_CAPACITY_UNSPECIFIED = 'GATEWAY_CAPACITY_UNSPECIFIED';
  /**
   * The gateway has 1 Gbps of aggregate processing capacity
   */
  public const CAPACITY_CAPACITY_1_GBPS = 'CAPACITY_1_GBPS';
  /**
   * The gateway has 10 Gbps of aggregate processing capacity
   */
  public const CAPACITY_CAPACITY_10_GBPS = 'CAPACITY_10_GBPS';
  protected $collection_key = 'ipRangeReservations';
  /**
   * Optional. The aggregate processing capacity of this gateway.
   *
   * @var string
   */
  public $capacity;
  /**
   * Output only. The list of Cloud Routers that are connected to this gateway.
   * Should be in the form: https://www.googleapis.com/compute/v1/projects/{proj
   * ect}/regions/{region}/routers/{router}
   *
   * @var string[]
   */
  public $cloudRouters;
  protected $ipRangeReservationsType = IpRangeReservation::class;
  protected $ipRangeReservationsDataType = 'array';
  /**
   * Output only. The URI of the connected SACAttachment. Should be in the form:
   * projects/{project}/locations/{location}/sacAttachments/{sac_attachment}
   *
   * @var string
   */
  public $sacAttachment;

  /**
   * Optional. The aggregate processing capacity of this gateway.
   *
   * Accepted values: GATEWAY_CAPACITY_UNSPECIFIED, CAPACITY_1_GBPS,
   * CAPACITY_10_GBPS
   *
   * @param self::CAPACITY_* $capacity
   */
  public function setCapacity($capacity)
  {
    $this->capacity = $capacity;
  }
  /**
   * @return self::CAPACITY_*
   */
  public function getCapacity()
  {
    return $this->capacity;
  }
  /**
   * Output only. The list of Cloud Routers that are connected to this gateway.
   * Should be in the form: https://www.googleapis.com/compute/v1/projects/{proj
   * ect}/regions/{region}/routers/{router}
   *
   * @param string[] $cloudRouters
   */
  public function setCloudRouters($cloudRouters)
  {
    $this->cloudRouters = $cloudRouters;
  }
  /**
   * @return string[]
   */
  public function getCloudRouters()
  {
    return $this->cloudRouters;
  }
  /**
   * Optional. A list of IP ranges that are reserved for this gateway's internal
   * intfrastructure.
   *
   * @param IpRangeReservation[] $ipRangeReservations
   */
  public function setIpRangeReservations($ipRangeReservations)
  {
    $this->ipRangeReservations = $ipRangeReservations;
  }
  /**
   * @return IpRangeReservation[]
   */
  public function getIpRangeReservations()
  {
    return $this->ipRangeReservations;
  }
  /**
   * Output only. The URI of the connected SACAttachment. Should be in the form:
   * projects/{project}/locations/{location}/sacAttachments/{sac_attachment}
   *
   * @param string $sacAttachment
   */
  public function setSacAttachment($sacAttachment)
  {
    $this->sacAttachment = $sacAttachment;
  }
  /**
   * @return string
   */
  public function getSacAttachment()
  {
    return $this->sacAttachment;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Gateway::class, 'Google_Service_Networkconnectivity_Gateway');
