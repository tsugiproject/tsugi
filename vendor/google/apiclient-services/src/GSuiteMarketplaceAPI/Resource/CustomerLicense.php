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

namespace Google\Service\GSuiteMarketplaceAPI\Resource;

use Google\Service\GSuiteMarketplaceAPI\CustomerLicense as CustomerLicenseModel;

/**
 * The "customerLicense" collection of methods.
 * Typical usage is:
 *  <code>
 *   $appsmarketService = new Google\Service\GSuiteMarketplaceAPI(...);
 *   $customerLicense = $appsmarketService->customerLicense;
 *  </code>
 */
class CustomerLicense extends \Google\Service\Resource
{
  /**
   * Gets the customer's licensing status to determine if they have access to a
   * given app. For more information, see [Getting app installation and licensing
   * details](https://developers.google.com/workspace/marketplace/example-calls-
   * marketplace-api). (customerLicense.get)
   *
   * @param string $applicationId The ID of the application.
   * @param string $customerId The ID of the customer.
   * @param array $optParams Optional parameters.
   * @return CustomerLicenseModel
   * @throws \Google\Service\Exception
   */
  public function get($applicationId, $customerId, $optParams = [])
  {
    $params = ['applicationId' => $applicationId, 'customerId' => $customerId];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], CustomerLicenseModel::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CustomerLicense::class, 'Google_Service_GSuiteMarketplaceAPI_Resource_CustomerLicense');
