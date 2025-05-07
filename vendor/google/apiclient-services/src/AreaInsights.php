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

namespace Google\Service;

use Google\Client;

/**
 * Service definition for AreaInsights (v1).
 *
 * <p>
 * Places Insights API.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://g3doc.corp.google.com/geo/platform/area_insights/README.md?cl=head" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class AreaInsights extends \Google\Service
{
  /** See, edit, configure, and delete your Google Cloud data and see the email address for your Google Account.. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";

  public $v1;
  public $rootUrlTemplate;

  /**
   * Constructs the internal representation of the AreaInsights service.
   *
   * @param Client|array $clientOrConfig The client used to deliver requests, or a
   *                                     config array to pass to a new Client instance.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct($clientOrConfig = [], $rootUrl = null)
  {
    parent::__construct($clientOrConfig);
    $this->rootUrl = $rootUrl ?: 'https://areainsights.googleapis.com/';
    $this->rootUrlTemplate = $rootUrl ?: 'https://areainsights.UNIVERSE_DOMAIN/';
    $this->servicePath = '';
    $this->batchPath = 'batch';
    $this->version = 'v1';
    $this->serviceName = 'areainsights';

    $this->v1 = new AreaInsights\Resource\V1(
        $this,
        $this->serviceName,
        'v1',
        [
          'methods' => [
            'computeInsights' => [
              'path' => 'v1:computeInsights',
              'httpMethod' => 'POST',
              'parameters' => [],
            ],
          ]
        ]
    );
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AreaInsights::class, 'Google_Service_AreaInsights');
