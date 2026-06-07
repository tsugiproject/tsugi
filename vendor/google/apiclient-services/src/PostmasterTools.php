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
 * Service definition for PostmasterTools (v2).
 *
 * <p>
 * The Postmaster Tools API is a RESTful API that provides programmatic access
 * to email traffic metrics (like spam reports, delivery errors etc) otherwise
 * available through the Gmail Postmaster Tools UI currently.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/workspace/gmail/postmaster" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class PostmasterTools extends \Google\Service
{
  /** Get email traffic metrics, manage domains, and manage domain users for the domains you have registered with Postmaster Tools. */
  const POSTMASTER =
      "https://www.googleapis.com/auth/postmaster";
  /** View and manage the domains you have registered with Postmaster Tools. */
  const POSTMASTER_DOMAIN =
      "https://www.googleapis.com/auth/postmaster.domain";
  /** Get email traffic metrics for the domains you have registered with Postmaster Tools. */
  const POSTMASTER_TRAFFIC_READONLY =
      "https://www.googleapis.com/auth/postmaster.traffic.readonly";

  public $domainStats;
  public $domains;
  public $domains_domainStats;
  public $rootUrlTemplate;

  /**
   * Constructs the internal representation of the PostmasterTools service.
   *
   * @param Client|array $clientOrConfig The client used to deliver requests, or a
   *                                     config array to pass to a new Client instance.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct($clientOrConfig = [], $rootUrl = null)
  {
    parent::__construct($clientOrConfig);
    $this->rootUrl = $rootUrl ?: 'https://gmailpostmastertools.googleapis.com/';
    $this->rootUrlTemplate = $rootUrl ?: 'https://gmailpostmastertools.UNIVERSE_DOMAIN/';
    $this->servicePath = '';
    $this->batchPath = 'batch';
    $this->version = 'v2';
    $this->serviceName = 'gmailpostmastertools';

    $this->domainStats = new PostmasterTools\Resource\DomainStats(
        $this,
        $this->serviceName,
        'domainStats',
        [
          'methods' => [
            'batchQuery' => [
              'path' => 'v2/domainStats:batchQuery',
              'httpMethod' => 'POST',
              'parameters' => [],
            ],
          ]
        ]
    );
    $this->domains = new PostmasterTools\Resource\Domains(
        $this,
        $this->serviceName,
        'domains',
        [
          'methods' => [
            'get' => [
              'path' => 'v2/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'getComplianceStatus' => [
              'path' => 'v2/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'list' => [
              'path' => 'v2/domains',
              'httpMethod' => 'GET',
              'parameters' => [
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
    $this->domains_domainStats = new PostmasterTools\Resource\DomainsDomainStats(
        $this,
        $this->serviceName,
        'domainStats',
        [
          'methods' => [
            'query' => [
              'path' => 'v2/{+parent}/domainStats:query',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],
          ]
        ]
    );
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PostmasterTools::class, 'Google_Service_PostmasterTools');
