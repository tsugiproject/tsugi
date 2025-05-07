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

namespace Google\Service\NetworkSecurity;

class AuthzPolicyCustomProvider extends \Google\Model
{
  protected $authzExtensionType = AuthzPolicyCustomProviderAuthzExtension::class;
  protected $authzExtensionDataType = '';
  protected $cloudIapType = AuthzPolicyCustomProviderCloudIap::class;
  protected $cloudIapDataType = '';

  /**
   * @param AuthzPolicyCustomProviderAuthzExtension
   */
  public function setAuthzExtension(AuthzPolicyCustomProviderAuthzExtension $authzExtension)
  {
    $this->authzExtension = $authzExtension;
  }
  /**
   * @return AuthzPolicyCustomProviderAuthzExtension
   */
  public function getAuthzExtension()
  {
    return $this->authzExtension;
  }
  /**
   * @param AuthzPolicyCustomProviderCloudIap
   */
  public function setCloudIap(AuthzPolicyCustomProviderCloudIap $cloudIap)
  {
    $this->cloudIap = $cloudIap;
  }
  /**
   * @return AuthzPolicyCustomProviderCloudIap
   */
  public function getCloudIap()
  {
    return $this->cloudIap;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AuthzPolicyCustomProvider::class, 'Google_Service_NetworkSecurity_AuthzPolicyCustomProvider');
