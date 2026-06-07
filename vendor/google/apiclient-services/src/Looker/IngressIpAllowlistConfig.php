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

namespace Google\Service\Looker;

class IngressIpAllowlistConfig extends \Google\Collection
{
  protected $collection_key = 'allowlistRules';
  protected $allowlistRulesType = IngressIpAllowlistRule::class;
  protected $allowlistRulesDataType = 'array';
  /**
   * Optional. Whether ingress IP allowlist functionality is enabled on the
   * Looker instance.
   *
   * @var bool
   */
  public $enabled;
  /**
   * Optional. Whether google service connections are enabled for the instance.
   *
   * @var bool
   */
  public $googleServicesEnabled;

  /**
   * Optional. List of IP range rules to allow ingress traffic.
   *
   * @param IngressIpAllowlistRule[] $allowlistRules
   */
  public function setAllowlistRules($allowlistRules)
  {
    $this->allowlistRules = $allowlistRules;
  }
  /**
   * @return IngressIpAllowlistRule[]
   */
  public function getAllowlistRules()
  {
    return $this->allowlistRules;
  }
  /**
   * Optional. Whether ingress IP allowlist functionality is enabled on the
   * Looker instance.
   *
   * @param bool $enabled
   */
  public function setEnabled($enabled)
  {
    $this->enabled = $enabled;
  }
  /**
   * @return bool
   */
  public function getEnabled()
  {
    return $this->enabled;
  }
  /**
   * Optional. Whether google service connections are enabled for the instance.
   *
   * @param bool $googleServicesEnabled
   */
  public function setGoogleServicesEnabled($googleServicesEnabled)
  {
    $this->googleServicesEnabled = $googleServicesEnabled;
  }
  /**
   * @return bool
   */
  public function getGoogleServicesEnabled()
  {
    return $this->googleServicesEnabled;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IngressIpAllowlistConfig::class, 'Google_Service_Looker_IngressIpAllowlistConfig');
