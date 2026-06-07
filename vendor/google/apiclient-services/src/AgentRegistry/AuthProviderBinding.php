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

namespace Google\Service\AgentRegistry;

class AuthProviderBinding extends \Google\Collection
{
  protected $collection_key = 'scopes';
  /**
   * Required. The resource name of the target AuthProvider. Format: *
   * `projects/{project}/locations/{location}/authProviders/{auth_provider}`
   *
   * @var string
   */
  public $authProvider;
  /**
   * Optional. The continue URI of the AuthProvider. The URI is used to
   * reauthenticate the user and finalize the managed OAuth flow.
   *
   * @var string
   */
  public $continueUri;
  /**
   * Optional. The list of OAuth2 scopes of the AuthProvider.
   *
   * @var string[]
   */
  public $scopes;

  /**
   * Required. The resource name of the target AuthProvider. Format: *
   * `projects/{project}/locations/{location}/authProviders/{auth_provider}`
   *
   * @param string $authProvider
   */
  public function setAuthProvider($authProvider)
  {
    $this->authProvider = $authProvider;
  }
  /**
   * @return string
   */
  public function getAuthProvider()
  {
    return $this->authProvider;
  }
  /**
   * Optional. The continue URI of the AuthProvider. The URI is used to
   * reauthenticate the user and finalize the managed OAuth flow.
   *
   * @param string $continueUri
   */
  public function setContinueUri($continueUri)
  {
    $this->continueUri = $continueUri;
  }
  /**
   * @return string
   */
  public function getContinueUri()
  {
    return $this->continueUri;
  }
  /**
   * Optional. The list of OAuth2 scopes of the AuthProvider.
   *
   * @param string[] $scopes
   */
  public function setScopes($scopes)
  {
    $this->scopes = $scopes;
  }
  /**
   * @return string[]
   */
  public function getScopes()
  {
    return $this->scopes;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AuthProviderBinding::class, 'Google_Service_AgentRegistry_AuthProviderBinding');
