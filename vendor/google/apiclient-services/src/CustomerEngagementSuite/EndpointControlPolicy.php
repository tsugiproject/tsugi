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

class EndpointControlPolicy extends \Google\Collection
{
  /**
   * Unspecified. This policy will be treated as VPCSC_ONLY.
   */
  public const ENFORCEMENT_SCOPE_ENFORCEMENT_SCOPE_UNSPECIFIED = 'ENFORCEMENT_SCOPE_UNSPECIFIED';
  /**
   * This policy applies only when VPC-SC is active.
   */
  public const ENFORCEMENT_SCOPE_VPCSC_ONLY = 'VPCSC_ONLY';
  /**
   * This policy ALWAYS applies, regardless of VPC-SC status.
   */
  public const ENFORCEMENT_SCOPE_ALWAYS = 'ALWAYS';
  protected $collection_key = 'allowedOrigins';
  /**
   * Optional. The allowed HTTP(s) origins that tools in the App are able to
   * directly call. The enforcement depends on the value of enforcement_scope
   * and the VPC-SC status of the project. If a port number is not provided, all
   * ports will be allowed. Otherwise, the port number must match exactly. For
   * example, "https://example.com" will match "https://example.com:443" and any
   * other port. "https://example.com:443" will only match
   * "https://example.com:443".
   *
   * @var string[]
   */
  public $allowedOrigins;
  /**
   * Optional. The scope in which this policy's allowed_origins list is
   * enforced.
   *
   * @var string
   */
  public $enforcementScope;

  /**
   * Optional. The allowed HTTP(s) origins that tools in the App are able to
   * directly call. The enforcement depends on the value of enforcement_scope
   * and the VPC-SC status of the project. If a port number is not provided, all
   * ports will be allowed. Otherwise, the port number must match exactly. For
   * example, "https://example.com" will match "https://example.com:443" and any
   * other port. "https://example.com:443" will only match
   * "https://example.com:443".
   *
   * @param string[] $allowedOrigins
   */
  public function setAllowedOrigins($allowedOrigins)
  {
    $this->allowedOrigins = $allowedOrigins;
  }
  /**
   * @return string[]
   */
  public function getAllowedOrigins()
  {
    return $this->allowedOrigins;
  }
  /**
   * Optional. The scope in which this policy's allowed_origins list is
   * enforced.
   *
   * Accepted values: ENFORCEMENT_SCOPE_UNSPECIFIED, VPCSC_ONLY, ALWAYS
   *
   * @param self::ENFORCEMENT_SCOPE_* $enforcementScope
   */
  public function setEnforcementScope($enforcementScope)
  {
    $this->enforcementScope = $enforcementScope;
  }
  /**
   * @return self::ENFORCEMENT_SCOPE_*
   */
  public function getEnforcementScope()
  {
    return $this->enforcementScope;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(EndpointControlPolicy::class, 'Google_Service_CustomerEngagementSuite_EndpointControlPolicy');
