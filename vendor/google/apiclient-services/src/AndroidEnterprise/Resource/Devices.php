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

namespace Google\Service\AndroidEnterprise\Resource;

use Google\Service\AndroidEnterprise\Device;
use Google\Service\AndroidEnterprise\DeviceState;
use Google\Service\AndroidEnterprise\DevicesListResponse;

/**
 * The "devices" collection of methods.
 * Typical usage is:
 *  <code>
 *   $androidenterpriseService = new Google\Service\AndroidEnterprise(...);
 *   $devices = $androidenterpriseService->devices;
 *  </code>
 */
class Devices extends \Google\Service\Resource
{
  /**
   * Uploads a report containing any changes in app states on the device since the
   * last report was generated. You can call this method up to 3 times every 24
   * hours for a given device. If you exceed the quota, then the Google Play EMM
   * API returns HTTP 429 Too Many Requests. (devices.forceReportUpload)
   *
   * @param string $enterpriseId The ID of the enterprise.
   * @param string $userId The ID of the user.
   * @param string $deviceId The ID of the device.
   * @param array $optParams Optional parameters.
   * @throws \Google\Service\Exception
   */
  public function forceReportUpload($enterpriseId, $userId, $deviceId, $optParams = [])
  {
    $params = ['enterpriseId' => $enterpriseId, 'userId' => $userId, 'deviceId' => $deviceId];
    $params = array_merge($params, $optParams);
    return $this->call('forceReportUpload', [$params]);
  }
  /**
   * Retrieves the details of a device. (devices.get)
   *
   * @param string $enterpriseId The ID of the enterprise.
   * @param string $userId The ID of the user.
   * @param string $deviceId The ID of the device.
   * @param array $optParams Optional parameters.
   * @return Device
   * @throws \Google\Service\Exception
   */
  public function get($enterpriseId, $userId, $deviceId, $optParams = [])
  {
    $params = ['enterpriseId' => $enterpriseId, 'userId' => $userId, 'deviceId' => $deviceId];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Device::class);
  }
  /**
   * Checks if a device can access Google apps and services for a user. Returns
   * whether access is "enabled" or "disabled". A "disabled" state prevents the
   * user's Managed Google Account on the device from successfully authenticating
   * with Google. This blocks access to most Google applications and services,
   * including Google Play, as the device cannot prove its entitlement to access
   * them. New devices default to "disabled". Important: Enforcement of this state
   * depends on the following conditions: * The user must be a managed google
   * account. * The enterprise must be a managed google domain. * Third-party
   * Android mobile management must be active in the Google Admin Console for the
   * user's Organizational Unit. If these conditions aren't met, access may still
   * be possible even in a "disabled" state. (devices.getState)
   *
   * @param string $enterpriseId The ID of the enterprise.
   * @param string $userId The ID of the user.
   * @param string $deviceId The ID of the device.
   * @param array $optParams Optional parameters.
   * @return DeviceState
   * @throws \Google\Service\Exception
   */
  public function getState($enterpriseId, $userId, $deviceId, $optParams = [])
  {
    $params = ['enterpriseId' => $enterpriseId, 'userId' => $userId, 'deviceId' => $deviceId];
    $params = array_merge($params, $optParams);
    return $this->call('getState', [$params], DeviceState::class);
  }
  /**
   * Retrieves the IDs of all of a user's devices. (devices.listDevices)
   *
   * @param string $enterpriseId The ID of the enterprise.
   * @param string $userId The ID of the user.
   * @param array $optParams Optional parameters.
   * @return DevicesListResponse
   * @throws \Google\Service\Exception
   */
  public function listDevices($enterpriseId, $userId, $optParams = [])
  {
    $params = ['enterpriseId' => $enterpriseId, 'userId' => $userId];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], DevicesListResponse::class);
  }
  /**
   * Sets whether a device's access to Google services (including Google Play) is
   * enabled or disabled for the specified user. Setting the state to "enabled"
   * allows the Google Account to access Google services, while "disabled" blocks
   * access by preventing OAuth token issuance. Preconditions for Enforcement: 1.
   * This setting is only effective for Google-managed users. 2. The enterprise
   * must be linked to a Google Managed Domain. 3. Enforcement requires third-
   * party Android mobile management to be enabled within the Google Admin Console
   * for the user's Organizational Unit. If these preconditions are not met,
   * changes to this state may be ignored. (devices.setState)
   *
   * @param string $enterpriseId The ID of the enterprise.
   * @param string $userId The ID of the user.
   * @param string $deviceId The ID of the device.
   * @param DeviceState $postBody
   * @param array $optParams Optional parameters.
   * @return DeviceState
   * @throws \Google\Service\Exception
   */
  public function setState($enterpriseId, $userId, $deviceId, DeviceState $postBody, $optParams = [])
  {
    $params = ['enterpriseId' => $enterpriseId, 'userId' => $userId, 'deviceId' => $deviceId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('setState', [$params], DeviceState::class);
  }
  /**
   * Updates the device policy. To ensure the policy is properly enforced, you
   * need to prevent unmanaged accounts from accessing Google Play by setting the
   * allowed_accounts in the managed configuration for the Google Play package.
   * See restrict accounts in Google Play. When provisioning a new device, you
   * should set the device policy using this method before adding the managed
   * Google Play Account to the device, otherwise the policy will not be applied
   * for a short period of time after adding the account to the device.
   * (devices.update)
   *
   * @param string $enterpriseId The ID of the enterprise.
   * @param string $userId The ID of the user.
   * @param string $deviceId The ID of the device.
   * @param Device $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask Mask that identifies which fields to update. If
   * not set, all modifiable fields will be modified. When set in a query
   * parameter, this field should be specified as updateMask=,,...
   * @return Device
   * @throws \Google\Service\Exception
   */
  public function update($enterpriseId, $userId, $deviceId, Device $postBody, $optParams = [])
  {
    $params = ['enterpriseId' => $enterpriseId, 'userId' => $userId, 'deviceId' => $deviceId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('update', [$params], Device::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Devices::class, 'Google_Service_AndroidEnterprise_Resource_Devices');
