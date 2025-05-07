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

namespace Google\Service\Dfareporting\Resource;

use Google\Service\Dfareporting\TvCampaignDetail;

/**
 * The "tvCampaignDetails" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google\Service\Dfareporting(...);
 *   $tvCampaignDetails = $dfareportingService->tvCampaignDetails;
 *  </code>
 */
class TvCampaignDetails extends \Google\Service\Resource
{
  /**
   * Gets one TvCampaignDetail by ID. (tvCampaignDetails.get)
   *
   * @param string $profileId Required. User profile ID associated with this
   * request.
   * @param string $id Required. TV Campaign ID.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string accountId Required. Account ID associated with this
   * request.
   * @return TvCampaignDetail
   * @throws \Google\Service\Exception
   */
  public function get($profileId, $id, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'id' => $id];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], TvCampaignDetail::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TvCampaignDetails::class, 'Google_Service_Dfareporting_Resource_TvCampaignDetails');
