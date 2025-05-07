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

use Google\Service\AirQuality\LookupForecastRequest;
use Google\Service\AirQuality\LookupForecastResponse;

/**
 * The "forecast" collection of methods.
 * Typical usage is:
 *  <code>
 *   $airqualityService = new Google\Service\AirQuality(...);
 *   $forecast = $airqualityService->forecast;
 *  </code>
 */
class Forecast extends \Google\Service\Resource
{
  /**
   * Returns air quality forecast for a specific location for a given time range.
   * (forecast.lookup)
   *
   * @param LookupForecastRequest $postBody
   * @param array $optParams Optional parameters.
   * @return LookupForecastResponse
   * @throws \Google\Service\Exception
   */
  public function lookup(LookupForecastRequest $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('lookup', [$params], LookupForecastResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Forecast::class, 'Google_Service_AirQuality_Resource_Forecast');
