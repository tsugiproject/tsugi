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

class LfA2aV1AuthenticationInfo extends \Google\Model
{
  /**
   * Push Notification credentials. Format depends on the scheme (e.g., token
   * for Bearer).
   *
   * @var string
   */
  public $credentials;
  /**
   * Required. HTTP Authentication Scheme from the [IANA
   * registry](https://www.iana.org/assignments/http-authschemes/). Examples:
   * `Bearer`, `Basic`, `Digest`. Scheme names are case-insensitive per [RFC
   * 9110 Section 11.1](https://www.rfc-editor.org/rfc/rfc9110#section-11.1).
   *
   * @var string
   */
  public $scheme;

  /**
   * Push Notification credentials. Format depends on the scheme (e.g., token
   * for Bearer).
   *
   * @param string $credentials
   */
  public function setCredentials($credentials)
  {
    $this->credentials = $credentials;
  }
  /**
   * @return string
   */
  public function getCredentials()
  {
    return $this->credentials;
  }
  /**
   * Required. HTTP Authentication Scheme from the [IANA
   * registry](https://www.iana.org/assignments/http-authschemes/). Examples:
   * `Bearer`, `Basic`, `Digest`. Scheme names are case-insensitive per [RFC
   * 9110 Section 11.1](https://www.rfc-editor.org/rfc/rfc9110#section-11.1).
   *
   * @param string $scheme
   */
  public function setScheme($scheme)
  {
    $this->scheme = $scheme;
  }
  /**
   * @return string
   */
  public function getScheme()
  {
    return $this->scheme;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LfA2aV1AuthenticationInfo::class, 'Google_Service_CustomerEngagementSuite_LfA2aV1AuthenticationInfo');
