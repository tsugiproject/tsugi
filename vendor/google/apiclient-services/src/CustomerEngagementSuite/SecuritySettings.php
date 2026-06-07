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

namespace Google\Service\CustomerEngagementSuite;

class SecuritySettings extends \Google\Model
{
  /**
   * Output only. Create time of the security settings.
   *
   * @var string
   */
  public $createTime;
  protected $endpointControlPolicyType = EndpointControlPolicy::class;
  protected $endpointControlPolicyDataType = '';
  /**
   * Output only. Etag of the security settings.
   *
   * @var string
   */
  public $etag;
  /**
   * Identifier. The unique identifier of the security settings. Format:
   * `projects/{project}/locations/{location}/securitySettings`
   *
   * @var string
   */
  public $name;
  /**
   * Output only. Last update time of the security settings.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. Create time of the security settings.
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
   * Optional. Endpoint control related settings.
   *
   * @param EndpointControlPolicy $endpointControlPolicy
   */
  public function setEndpointControlPolicy(EndpointControlPolicy $endpointControlPolicy)
  {
    $this->endpointControlPolicy = $endpointControlPolicy;
  }
  /**
   * @return EndpointControlPolicy
   */
  public function getEndpointControlPolicy()
  {
    return $this->endpointControlPolicy;
  }
  /**
   * Output only. Etag of the security settings.
   *
   * @param string $etag
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * Identifier. The unique identifier of the security settings. Format:
   * `projects/{project}/locations/{location}/securitySettings`
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
   * Output only. Last update time of the security settings.
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
class_alias(SecuritySettings::class, 'Google_Service_CustomerEngagementSuite_SecuritySettings');
