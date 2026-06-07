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

class RemoteTransportProfile extends \Google\Collection
{
  /**
   * Unspecified key provisioning flow.
   */
  public const FLOW_KEY_PROVISIONING_FLOW_UNSPECIFIED = 'KEY_PROVISIONING_FLOW_UNSPECIFIED';
  /**
   * The activationKey field on the Transport must be included in a create or
   * patch request to establish connectivity.
   */
  public const FLOW_INPUT_ONLY = 'INPUT_ONLY';
  /**
   * The generatedActivationKey field is populated and must be read from the
   * resource and passed into the other provider.
   */
  public const FLOW_OUTPUT_ONLY = 'OUTPUT_ONLY';
  /**
   * Both activation key fields are allowed for establishing connectivity. If a
   * key is input, the generated key is still present after provisioning is
   * finished.
   */
  public const FLOW_INPUT_OR_OUTPUT = 'INPUT_OR_OUTPUT';
  /**
   * Unspecified state.
   */
  public const ORDER_STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * Not enough capacity for customers to order.
   */
  public const ORDER_STATE_CLOSED = 'CLOSED';
  /**
   * Enough capacity to fulfill an order.
   */
  public const ORDER_STATE_OPEN = 'OPEN';
  /**
   * Unspecified service level availability.
   */
  public const SLA_SERVICE_LEVEL_AVAILABILITY_UNSPECIFIED = 'SERVICE_LEVEL_AVAILABILITY_UNSPECIFIED';
  /**
   * This represents a 99.9% service level on the availability of the configured
   * connectivity.
   */
  public const SLA_HIGH = 'HIGH';
  /**
   * This represents a 99.99% service level on the availability of the
   * configured connectivity.
   */
  public const SLA_MAXIMUM = 'MAXIMUM';
  protected $collection_key = 'supportedBandwidths';
  /**
   * Output only. Description of the profile.
   *
   * @var string
   */
  public $description;
  /**
   * Output only. Human readable name of this profile, used to identify this
   * profile in the UI.
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. Type of provisioning flows supported by this profile.
   *
   * @var string
   */
  public $flow;
  /**
   * Output only. Labels as key value pairs.
   *
   * @var string[]
   */
  public $labels;
  /**
   * Identifier. Name of the resource in the format of $provider-$site.
   *
   * @var string
   */
  public $name;
  /**
   * Output only. Order state for this profile.
   *
   * @var string
   */
  public $orderState;
  /**
   * Output only. Name of the provider on the other end of this profile. E.g.
   * “Amazon Web Services” or “Microsoft Azure”.
   *
   * @var string
   */
  public $provider;
  /**
   * Output only. If the profile is a Cloud Service Provider with compute
   * resources, this is populated with the region where connectivity is being
   * established. If the profile provides facility-level selection, this is an
   * identity of the facility any connections on this profile are going through.
   *
   * @var string
   */
  public $providerSite;
  /**
   * Output only. Availability class that will be configured for this particular
   * RemoteTransportProfile.
   *
   * @var string
   */
  public $sla;
  /**
   * Output only. List of bandwidth enum values that are supported by this
   * profile.
   *
   * @var string[]
   */
  public $supportedBandwidths;

  /**
   * Output only. Description of the profile.
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
   * Output only. Human readable name of this profile, used to identify this
   * profile in the UI.
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
   * Output only. Type of provisioning flows supported by this profile.
   *
   * Accepted values: KEY_PROVISIONING_FLOW_UNSPECIFIED, INPUT_ONLY,
   * OUTPUT_ONLY, INPUT_OR_OUTPUT
   *
   * @param self::FLOW_* $flow
   */
  public function setFlow($flow)
  {
    $this->flow = $flow;
  }
  /**
   * @return self::FLOW_*
   */
  public function getFlow()
  {
    return $this->flow;
  }
  /**
   * Output only. Labels as key value pairs.
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
   * Identifier. Name of the resource in the format of $provider-$site.
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
   * Output only. Order state for this profile.
   *
   * Accepted values: STATE_UNSPECIFIED, CLOSED, OPEN
   *
   * @param self::ORDER_STATE_* $orderState
   */
  public function setOrderState($orderState)
  {
    $this->orderState = $orderState;
  }
  /**
   * @return self::ORDER_STATE_*
   */
  public function getOrderState()
  {
    return $this->orderState;
  }
  /**
   * Output only. Name of the provider on the other end of this profile. E.g.
   * “Amazon Web Services” or “Microsoft Azure”.
   *
   * @param string $provider
   */
  public function setProvider($provider)
  {
    $this->provider = $provider;
  }
  /**
   * @return string
   */
  public function getProvider()
  {
    return $this->provider;
  }
  /**
   * Output only. If the profile is a Cloud Service Provider with compute
   * resources, this is populated with the region where connectivity is being
   * established. If the profile provides facility-level selection, this is an
   * identity of the facility any connections on this profile are going through.
   *
   * @param string $providerSite
   */
  public function setProviderSite($providerSite)
  {
    $this->providerSite = $providerSite;
  }
  /**
   * @return string
   */
  public function getProviderSite()
  {
    return $this->providerSite;
  }
  /**
   * Output only. Availability class that will be configured for this particular
   * RemoteTransportProfile.
   *
   * Accepted values: SERVICE_LEVEL_AVAILABILITY_UNSPECIFIED, HIGH, MAXIMUM
   *
   * @param self::SLA_* $sla
   */
  public function setSla($sla)
  {
    $this->sla = $sla;
  }
  /**
   * @return self::SLA_*
   */
  public function getSla()
  {
    return $this->sla;
  }
  /**
   * Output only. List of bandwidth enum values that are supported by this
   * profile.
   *
   * @param string[] $supportedBandwidths
   */
  public function setSupportedBandwidths($supportedBandwidths)
  {
    $this->supportedBandwidths = $supportedBandwidths;
  }
  /**
   * @return string[]
   */
  public function getSupportedBandwidths()
  {
    return $this->supportedBandwidths;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RemoteTransportProfile::class, 'Google_Service_Networkconnectivity_RemoteTransportProfile');
