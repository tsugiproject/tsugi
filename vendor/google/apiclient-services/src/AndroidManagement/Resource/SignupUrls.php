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

namespace Google\Service\AndroidManagement\Resource;

use Google\Service\AndroidManagement\SignupUrl;

/**
 * The "signupUrls" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidmanagementService = new Google\Service\AndroidManagement(...);
 *   $signupUrls = $androidmanagementService->signupUrls;
 *  </code>
 */
class SignupUrls extends \Google\Service\Resource
{
  /**
   * Creates an enterprise signup URL. (signupUrls.create)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string adminEmail Optional. Email address used to prefill the
   * admin field of the enterprise signup form. This value is a hint only and can
   * be altered by the user. If allowedDomains is non-empty then this must belong
   * to one of the allowedDomains.
   * @opt_param string allowedDomains Optional. A list of domains that are
   * permitted for the admin email. The IT admin cannot enter an email address
   * with a domain name that is not in this list. Subdomains of domains in this
   * list are not allowed but can be allowed by adding a second entry which has *.
   * prefixed to the domain name (e.g. *.example.com). If the field is not present
   * or is an empty list then the IT admin is free to use any valid domain name.
   * Personal email domains are always allowed, but will result in the creation of
   * a managed Google Play Accounts enterprise.
   * @opt_param string callbackUrl The callback URL that the admin will be
   * redirected to after successfully creating an enterprise. Before redirecting
   * there the system will add a query parameter to this URL named enterpriseToken
   * which will contain an opaque token to be used for the create enterprise
   * request. The URL will be parsed then reformatted in order to add the
   * enterpriseToken parameter, so there may be some minor formatting changes.
   * @opt_param string projectId The ID of the Google Cloud Platform project which
   * will own the enterprise.
   * @return SignupUrl
   * @throws \Google\Service\Exception
   */
  public function create($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], SignupUrl::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SignupUrls::class, 'Google_Service_AndroidManagement_Resource_SignupUrls');
