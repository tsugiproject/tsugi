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
 * Service definition for PolicyAnalyzer (v1).
 *
 * <p>
</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://www.google.com" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class PolicyAnalyzer extends \Google\Service
{
  /** See, edit, configure, and delete your Google Cloud data and see the email address for your Google Account.. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";

  public $folders_locations_activityTypes_activities;
  public $organizations_locations_activityTypes_activities;
  public $projects_locations_activityTypes_activities;
  public $rootUrlTemplate;

  /**
   * Constructs the internal representation of the PolicyAnalyzer service.
   *
   * @param Client|array $clientOrConfig The client used to deliver requests, or a
   *                                     config array to pass to a new Client instance.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct($clientOrConfig = [], $rootUrl = null)
  {
    parent::__construct($clientOrConfig);
    $this->rootUrl = $rootUrl ?: 'https://policyanalyzer.googleapis.com/';
    $this->rootUrlTemplate = $rootUrl ?: 'https://policyanalyzer.UNIVERSE_DOMAIN/';
    $this->servicePath = '';
    $this->batchPath = 'batch';
    $this->version = 'v1';
    $this->serviceName = 'policyanalyzer';

    $this->folders_locations_activityTypes_activities = new PolicyAnalyzer\Resource\FoldersLocationsActivityTypesActivities(
        $this,
        $this->serviceName,
        'activities',
        [
          'methods' => [
            'query' => [
              'path' => 'v1/{+parent}/activities:query',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'filter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->organizations_locations_activityTypes_activities = new PolicyAnalyzer\Resource\OrganizationsLocationsActivityTypesActivities(
        $this,
        $this->serviceName,
        'activities',
        [
          'methods' => [
            'query' => [
              'path' => 'v1/{+parent}/activities:query',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'filter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_locations_activityTypes_activities = new PolicyAnalyzer\Resource\ProjectsLocationsActivityTypesActivities(
        $this,
        $this->serviceName,
        'activities',
        [
          'methods' => [
            'query' => [
              'path' => 'v1/{+parent}/activities:query',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'filter' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
                'pageSize' => [
                  'location' => 'query',
                  'type' => 'integer',
                ],
                'pageToken' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PolicyAnalyzer::class, 'Google_Service_PolicyAnalyzer');
