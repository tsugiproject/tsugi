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

class HealthCheck extends \Google\Collection
{
  protected $collection_key = 'sourceRegions';
  /**
   * @var int
   */
  public $checkIntervalSec;
  /**
   * @var string
   */
  public $creationTimestamp;
  /**
   * @var string
   */
  public $description;
  protected $grpcHealthCheckType = GRPCHealthCheck::class;
  protected $grpcHealthCheckDataType = '';
  /**
   * @var int
   */
  public $healthyThreshold;
  protected $http2HealthCheckType = HTTP2HealthCheck::class;
  protected $http2HealthCheckDataType = '';
  protected $httpHealthCheckType = HTTPHealthCheck::class;
  protected $httpHealthCheckDataType = '';
  protected $httpsHealthCheckType = HTTPSHealthCheck::class;
  protected $httpsHealthCheckDataType = '';
  /**
   * @var string
   */
  public $id;
  /**
   * @var string
   */
  public $kind;
  protected $logConfigType = HealthCheckLogConfig::class;
  protected $logConfigDataType = '';
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $region;
  /**
   * @var string
   */
  public $selfLink;
  /**
   * @var string[]
   */
  public $sourceRegions;
  protected $sslHealthCheckType = SSLHealthCheck::class;
  protected $sslHealthCheckDataType = '';
  protected $tcpHealthCheckType = TCPHealthCheck::class;
  protected $tcpHealthCheckDataType = '';
  /**
   * @var int
   */
  public $timeoutSec;
  /**
   * @var string
   */
  public $type;
  /**
   * @var int
   */
  public $unhealthyThreshold;

  /**
   * @param int
   */
  public function setCheckIntervalSec($checkIntervalSec)
  {
    $this->checkIntervalSec = $checkIntervalSec;
  }
  /**
   * @return int
   */
  public function getCheckIntervalSec()
  {
    return $this->checkIntervalSec;
  }
  /**
   * @param string
   */
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  /**
   * @return string
   */
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
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
   * @param GRPCHealthCheck
   */
  public function setGrpcHealthCheck(GRPCHealthCheck $grpcHealthCheck)
  {
    $this->grpcHealthCheck = $grpcHealthCheck;
  }
  /**
   * @return GRPCHealthCheck
   */
  public function getGrpcHealthCheck()
  {
    return $this->grpcHealthCheck;
  }
  /**
   * @param int
   */
  public function setHealthyThreshold($healthyThreshold)
  {
    $this->healthyThreshold = $healthyThreshold;
  }
  /**
   * @return int
   */
  public function getHealthyThreshold()
  {
    return $this->healthyThreshold;
  }
  /**
   * @param HTTP2HealthCheck
   */
  public function setHttp2HealthCheck(HTTP2HealthCheck $http2HealthCheck)
  {
    $this->http2HealthCheck = $http2HealthCheck;
  }
  /**
   * @return HTTP2HealthCheck
   */
  public function getHttp2HealthCheck()
  {
    return $this->http2HealthCheck;
  }
  /**
   * @param HTTPHealthCheck
   */
  public function setHttpHealthCheck(HTTPHealthCheck $httpHealthCheck)
  {
    $this->httpHealthCheck = $httpHealthCheck;
  }
  /**
   * @return HTTPHealthCheck
   */
  public function getHttpHealthCheck()
  {
    return $this->httpHealthCheck;
  }
  /**
   * @param HTTPSHealthCheck
   */
  public function setHttpsHealthCheck(HTTPSHealthCheck $httpsHealthCheck)
  {
    $this->httpsHealthCheck = $httpsHealthCheck;
  }
  /**
   * @return HTTPSHealthCheck
   */
  public function getHttpsHealthCheck()
  {
    return $this->httpsHealthCheck;
  }
  /**
   * @param string
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
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
   * @param HealthCheckLogConfig
   */
  public function setLogConfig(HealthCheckLogConfig $logConfig)
  {
    $this->logConfig = $logConfig;
  }
  /**
   * @return HealthCheckLogConfig
   */
  public function getLogConfig()
  {
    return $this->logConfig;
  }
  /**
   * @param string
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
   * @param string
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
  /**
   * @param string
   */
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  /**
   * @return string
   */
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * @param string[]
   */
  public function setSourceRegions($sourceRegions)
  {
    $this->sourceRegions = $sourceRegions;
  }
  /**
   * @return string[]
   */
  public function getSourceRegions()
  {
    return $this->sourceRegions;
  }
  /**
   * @param SSLHealthCheck
   */
  public function setSslHealthCheck(SSLHealthCheck $sslHealthCheck)
  {
    $this->sslHealthCheck = $sslHealthCheck;
  }
  /**
   * @return SSLHealthCheck
   */
  public function getSslHealthCheck()
  {
    return $this->sslHealthCheck;
  }
  /**
   * @param TCPHealthCheck
   */
  public function setTcpHealthCheck(TCPHealthCheck $tcpHealthCheck)
  {
    $this->tcpHealthCheck = $tcpHealthCheck;
  }
  /**
   * @return TCPHealthCheck
   */
  public function getTcpHealthCheck()
  {
    return $this->tcpHealthCheck;
  }
  /**
   * @param int
   */
  public function setTimeoutSec($timeoutSec)
  {
    $this->timeoutSec = $timeoutSec;
  }
  /**
   * @return int
   */
  public function getTimeoutSec()
  {
    return $this->timeoutSec;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
  /**
   * @param int
   */
  public function setUnhealthyThreshold($unhealthyThreshold)
  {
    $this->unhealthyThreshold = $unhealthyThreshold;
  }
  /**
   * @return int
   */
  public function getUnhealthyThreshold()
  {
    return $this->unhealthyThreshold;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HealthCheck::class, 'Google_Service_Compute_HealthCheck');
