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
 * Service definition for Firebaseappcheck (v1).
 *
 * <p>
 * Firebase App Check works alongside other Firebase services to help protect
 * your backend resources from abuse, such as billing fraud or phishing.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://firebase.google.com/docs/app-check" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Firebaseappcheck extends \Google\Service
{
  /** See, edit, configure, and delete your Google Cloud data and see the email address for your Google Account.. */
  const CLOUD_PLATFORM =
      "https://www.googleapis.com/auth/cloud-platform";
  /** View and administer all your Firebase data and settings. */
  const FIREBASE =
      "https://www.googleapis.com/auth/firebase";

  public $jwks;
  public $oauthClients;
  public $projects_apps;
  public $projects_apps_appAttestConfig;
  public $projects_apps_debugTokens;
  public $projects_apps_deviceCheckConfig;
  public $projects_apps_playIntegrityConfig;
  public $projects_apps_recaptchaEnterpriseConfig;
  public $projects_apps_recaptchaV3Config;
  public $projects_apps_safetyNetConfig;
  public $projects_services;
  public $projects_services_resourcePolicies;
  public $rootUrlTemplate;

  /**
   * Constructs the internal representation of the Firebaseappcheck service.
   *
   * @param Client|array $clientOrConfig The client used to deliver requests, or a
   *                                     config array to pass to a new Client instance.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct($clientOrConfig = [], $rootUrl = null)
  {
    parent::__construct($clientOrConfig);
    $this->rootUrl = $rootUrl ?: 'https://firebaseappcheck.googleapis.com/';
    $this->rootUrlTemplate = $rootUrl ?: 'https://firebaseappcheck.UNIVERSE_DOMAIN/';
    $this->servicePath = '';
    $this->batchPath = 'batch';
    $this->version = 'v1';
    $this->serviceName = 'firebaseappcheck';

    $this->jwks = new Firebaseappcheck\Resource\Jwks(
        $this,
        $this->serviceName,
        'jwks',
        [
          'methods' => [
            'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],
          ]
        ]
    );
    $this->oauthClients = new Firebaseappcheck\Resource\OauthClients(
        $this,
        $this->serviceName,
        'oauthClients',
        [
          'methods' => [
            'exchangeAppAttestAssertion' => [
              'path' => 'v1/{+app}:exchangeAppAttestAssertion',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeAppAttestAttestation' => [
              'path' => 'v1/{+app}:exchangeAppAttestAttestation',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeDebugToken' => [
              'path' => 'v1/{+app}:exchangeDebugToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'generateAppAttestChallenge' => [
              'path' => 'v1/{+app}:generateAppAttestChallenge',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps = new Firebaseappcheck\Resource\ProjectsApps(
        $this,
        $this->serviceName,
        'apps',
        [
          'methods' => [
            'exchangeAppAttestAssertion' => [
              'path' => 'v1/{+app}:exchangeAppAttestAssertion',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeAppAttestAttestation' => [
              'path' => 'v1/{+app}:exchangeAppAttestAttestation',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeCustomToken' => [
              'path' => 'v1/{+app}:exchangeCustomToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeDebugToken' => [
              'path' => 'v1/{+app}:exchangeDebugToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeDeviceCheckToken' => [
              'path' => 'v1/{+app}:exchangeDeviceCheckToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangePlayIntegrityToken' => [
              'path' => 'v1/{+app}:exchangePlayIntegrityToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeRecaptchaEnterpriseToken' => [
              'path' => 'v1/{+app}:exchangeRecaptchaEnterpriseToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeRecaptchaV3Token' => [
              'path' => 'v1/{+app}:exchangeRecaptchaV3Token',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'exchangeSafetyNetToken' => [
              'path' => 'v1/{+app}:exchangeSafetyNetToken',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'generateAppAttestChallenge' => [
              'path' => 'v1/{+app}:generateAppAttestChallenge',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'generatePlayIntegrityChallenge' => [
              'path' => 'v1/{+app}:generatePlayIntegrityChallenge',
              'httpMethod' => 'POST',
              'parameters' => [
                'app' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_appAttestConfig = new Firebaseappcheck\Resource\ProjectsAppsAppAttestConfig(
        $this,
        $this->serviceName,
        'appAttestConfig',
        [
          'methods' => [
            'batchGet' => [
              'path' => 'v1/{+parent}/apps/-/appAttestConfig:batchGet',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'names' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_debugTokens = new Firebaseappcheck\Resource\ProjectsAppsDebugTokens(
        $this,
        $this->serviceName,
        'debugTokens',
        [
          'methods' => [
            'create' => [
              'path' => 'v1/{+parent}/debugTokens',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'delete' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'v1/{+parent}/debugTokens',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
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
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_deviceCheckConfig = new Firebaseappcheck\Resource\ProjectsAppsDeviceCheckConfig(
        $this,
        $this->serviceName,
        'deviceCheckConfig',
        [
          'methods' => [
            'batchGet' => [
              'path' => 'v1/{+parent}/apps/-/deviceCheckConfig:batchGet',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'names' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_playIntegrityConfig = new Firebaseappcheck\Resource\ProjectsAppsPlayIntegrityConfig(
        $this,
        $this->serviceName,
        'playIntegrityConfig',
        [
          'methods' => [
            'batchGet' => [
              'path' => 'v1/{+parent}/apps/-/playIntegrityConfig:batchGet',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'names' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_recaptchaEnterpriseConfig = new Firebaseappcheck\Resource\ProjectsAppsRecaptchaEnterpriseConfig(
        $this,
        $this->serviceName,
        'recaptchaEnterpriseConfig',
        [
          'methods' => [
            'batchGet' => [
              'path' => 'v1/{+parent}/apps/-/recaptchaEnterpriseConfig:batchGet',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'names' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_recaptchaV3Config = new Firebaseappcheck\Resource\ProjectsAppsRecaptchaV3Config(
        $this,
        $this->serviceName,
        'recaptchaV3Config',
        [
          'methods' => [
            'batchGet' => [
              'path' => 'v1/{+parent}/apps/-/recaptchaV3Config:batchGet',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'names' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_apps_safetyNetConfig = new Firebaseappcheck\Resource\ProjectsAppsSafetyNetConfig(
        $this,
        $this->serviceName,
        'safetyNetConfig',
        [
          'methods' => [
            'batchGet' => [
              'path' => 'v1/{+parent}/apps/-/safetyNetConfig:batchGet',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'names' => [
                  'location' => 'query',
                  'type' => 'string',
                  'repeated' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_services = new Firebaseappcheck\Resource\ProjectsServices(
        $this,
        $this->serviceName,
        'services',
        [
          'methods' => [
            'batchUpdate' => [
              'path' => 'v1/{+parent}/services:batchUpdate',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'v1/{+parent}/services',
              'httpMethod' => 'GET',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
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
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],
          ]
        ]
    );
    $this->projects_services_resourcePolicies = new Firebaseappcheck\Resource\ProjectsServicesResourcePolicies(
        $this,
        $this->serviceName,
        'resourcePolicies',
        [
          'methods' => [
            'batchUpdate' => [
              'path' => 'v1/{+parent}/resourcePolicies:batchUpdate',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'create' => [
              'path' => 'v1/{+parent}/resourcePolicies',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'delete' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'etag' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'get' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'v1/{+parent}/resourcePolicies',
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
            ],'patch' => [
              'path' => 'v1/{+name}',
              'httpMethod' => 'PATCH',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'updateMask' => [
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
class_alias(Firebaseappcheck::class, 'Google_Service_Firebaseappcheck');
