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

namespace Google\Service\ServiceNetworking;

class BackendRule extends \Google\Model
{
  /**
   * @var string
   */
  public $address;
  public $deadline;
  /**
   * @var bool
   */
  public $disableAuth;
  /**
   * @var string
   */
  public $jwtAudience;
  /**
   * @var string
   */
  public $loadBalancingPolicy;
  public $minDeadline;
  public $operationDeadline;
  protected $overridesByRequestProtocolType = BackendRule::class;
  protected $overridesByRequestProtocolDataType = 'map';
  /**
   * @var string
   */
  public $pathTranslation;
  /**
   * @var string
   */
  public $protocol;
  /**
   * @var string
   */
  public $selector;

  /**
   * @param string
   */
  public function setAddress($address)
  {
    $this->address = $address;
  }
  /**
   * @return string
   */
  public function getAddress()
  {
    return $this->address;
  }
  public function setDeadline($deadline)
  {
    $this->deadline = $deadline;
  }
  public function getDeadline()
  {
    return $this->deadline;
  }
  /**
   * @param bool
   */
  public function setDisableAuth($disableAuth)
  {
    $this->disableAuth = $disableAuth;
  }
  /**
   * @return bool
   */
  public function getDisableAuth()
  {
    return $this->disableAuth;
  }
  /**
   * @param string
   */
  public function setJwtAudience($jwtAudience)
  {
    $this->jwtAudience = $jwtAudience;
  }
  /**
   * @return string
   */
  public function getJwtAudience()
  {
    return $this->jwtAudience;
  }
  /**
   * @param string
   */
  public function setLoadBalancingPolicy($loadBalancingPolicy)
  {
    $this->loadBalancingPolicy = $loadBalancingPolicy;
  }
  /**
   * @return string
   */
  public function getLoadBalancingPolicy()
  {
    return $this->loadBalancingPolicy;
  }
  public function setMinDeadline($minDeadline)
  {
    $this->minDeadline = $minDeadline;
  }
  public function getMinDeadline()
  {
    return $this->minDeadline;
  }
  public function setOperationDeadline($operationDeadline)
  {
    $this->operationDeadline = $operationDeadline;
  }
  public function getOperationDeadline()
  {
    return $this->operationDeadline;
  }
  /**
   * @param BackendRule[]
   */
  public function setOverridesByRequestProtocol($overridesByRequestProtocol)
  {
    $this->overridesByRequestProtocol = $overridesByRequestProtocol;
  }
  /**
   * @return BackendRule[]
   */
  public function getOverridesByRequestProtocol()
  {
    return $this->overridesByRequestProtocol;
  }
  /**
   * @param string
   */
  public function setPathTranslation($pathTranslation)
  {
    $this->pathTranslation = $pathTranslation;
  }
  /**
   * @return string
   */
  public function getPathTranslation()
  {
    return $this->pathTranslation;
  }
  /**
   * @param string
   */
  public function setProtocol($protocol)
  {
    $this->protocol = $protocol;
  }
  /**
   * @return string
   */
  public function getProtocol()
  {
    return $this->protocol;
  }
  /**
   * @param string
   */
  public function setSelector($selector)
  {
    $this->selector = $selector;
  }
  /**
   * @return string
   */
  public function getSelector()
  {
    return $this->selector;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BackendRule::class, 'Google_Service_ServiceNetworking_BackendRule');
