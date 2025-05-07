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

namespace Google\Service\Integrations;

class EnterpriseCrmEventbusProtoExternalTraffic extends \Google\Model
{
  /**
   * @var bool
   */
  public $enableInternalIp;
  /**
   * @var string
   */
  public $gcpProjectId;
  /**
   * @var string
   */
  public $gcpProjectNumber;
  /**
   * @var string
   */
  public $location;
  /**
   * @var bool
   */
  public $retryRequestForQuota;
  /**
   * @var string
   */
  public $source;

  /**
   * @param bool
   */
  public function setEnableInternalIp($enableInternalIp)
  {
    $this->enableInternalIp = $enableInternalIp;
  }
  /**
   * @return bool
   */
  public function getEnableInternalIp()
  {
    return $this->enableInternalIp;
  }
  /**
   * @param string
   */
  public function setGcpProjectId($gcpProjectId)
  {
    $this->gcpProjectId = $gcpProjectId;
  }
  /**
   * @return string
   */
  public function getGcpProjectId()
  {
    return $this->gcpProjectId;
  }
  /**
   * @param string
   */
  public function setGcpProjectNumber($gcpProjectNumber)
  {
    $this->gcpProjectNumber = $gcpProjectNumber;
  }
  /**
   * @return string
   */
  public function getGcpProjectNumber()
  {
    return $this->gcpProjectNumber;
  }
  /**
   * @param string
   */
  public function setLocation($location)
  {
    $this->location = $location;
  }
  /**
   * @return string
   */
  public function getLocation()
  {
    return $this->location;
  }
  /**
   * @param bool
   */
  public function setRetryRequestForQuota($retryRequestForQuota)
  {
    $this->retryRequestForQuota = $retryRequestForQuota;
  }
  /**
   * @return bool
   */
  public function getRetryRequestForQuota()
  {
    return $this->retryRequestForQuota;
  }
  /**
   * @param string
   */
  public function setSource($source)
  {
    $this->source = $source;
  }
  /**
   * @return string
   */
  public function getSource()
  {
    return $this->source;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EnterpriseCrmEventbusProtoExternalTraffic::class, 'Google_Service_Integrations_EnterpriseCrmEventbusProtoExternalTraffic');
