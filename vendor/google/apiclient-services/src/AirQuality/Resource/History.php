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

namespace Google\Service\AirQuality\Resource;

use Google\Service\AirQuality\LookupHistoryRequest;
use Google\Service\AirQuality\LookupHistoryResponse;

/**
 * The "history" collection of methods.
 * Typical usage is:
 *  <code>
 *   $airqualityService = new Google\Service\AirQuality(...);
 *   $history = $airqualityService->history;
 *  </code>
 */
class History extends \Google\Service\Resource
{
  /**
   * Returns air quality history for a specific location for a given time range.
   * (history.lookup)
   *
   * @param LookupHistoryRequest $postBody
   * @param array $optParams Optional parameters.
   * @return LookupHistoryResponse
   * @throws \Google\Service\Exception
   */
  public function lookup(LookupHistoryRequest $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('lookup', [$params], LookupHistoryResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(History::class, 'Google_Service_AirQuality_Resource_History');
