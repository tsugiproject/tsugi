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

namespace Google\Service\Compute;

class HealthStatusForNetworkEndpoint extends \Google\Model
{
  protected $backendServiceType = BackendServiceReference::class;
  protected $backendServiceDataType = '';
  protected $forwardingRuleType = ForwardingRuleReference::class;
  protected $forwardingRuleDataType = '';
  protected $healthCheckType = HealthCheckReference::class;
  protected $healthCheckDataType = '';
  protected $healthCheckServiceType = HealthCheckServiceReference::class;
  protected $healthCheckServiceDataType = '';
  /**
   * @var string
   */
  public $healthState;
  /**
   * @var string
   */
  public $ipv6HealthState;

  /**
   * @param BackendServiceReference
   */
  public function setBackendService(BackendServiceReference $backendService)
  {
    $this->backendService = $backendService;
  }
  /**
   * @return BackendServiceReference
   */
  public function getBackendService()
  {
    return $this->backendService;
  }
  /**
   * @param ForwardingRuleReference
   */
  public function setForwardingRule(ForwardingRuleReference $forwardingRule)
  {
    $this->forwardingRule = $forwardingRule;
  }
  /**
   * @return ForwardingRuleReference
   */
  public function getForwardingRule()
  {
    return $this->forwardingRule;
  }
  /**
   * @param HealthCheckReference
   */
  public function setHealthCheck(HealthCheckReference $healthCheck)
  {
    $this->healthCheck = $healthCheck;
  }
  /**
   * @return HealthCheckReference
   */
  public function getHealthCheck()
  {
    return $this->healthCheck;
  }
  /**
   * @param HealthCheckServiceReference
   */
  public function setHealthCheckService(HealthCheckServiceReference $healthCheckService)
  {
    $this->healthCheckService = $healthCheckService;
  }
  /**
   * @return HealthCheckServiceReference
   */
  public function getHealthCheckService()
  {
    return $this->healthCheckService;
  }
  /**
   * @param string
   */
  public function setHealthState($healthState)
  {
    $this->healthState = $healthState;
  }
  /**
   * @return string
   */
  public function getHealthState()
  {
    return $this->healthState;
  }
  /**
   * @param string
   */
  public function setIpv6HealthState($ipv6HealthState)
  {
    $this->ipv6HealthState = $ipv6HealthState;
  }
  /**
   * @return string
   */
  public function getIpv6HealthState()
  {
    return $this->ipv6HealthState;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HealthStatusForNetworkEndpoint::class, 'Google_Service_Compute_HealthStatusForNetworkEndpoint');
