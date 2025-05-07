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
 * Service definition for Merchant (reviews_v1beta).
 *
 * <p>
 * Programmatically manage your Merchant Center Accounts.</p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.devsite.corp.google.com/merchant/api" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */
class Merchant extends \Google\Service
{
  /** Manage your product listings and accounts for Google Shopping. */
  const CONTENT =
      "https://www.googleapis.com/auth/content";

  public $accounts_merchantReviews;
  public $accounts_productReviews;
  public $rootUrlTemplate;

  /**
   * Constructs the internal representation of the Merchant service.
   *
   * @param Client|array $clientOrConfig The client used to deliver requests, or a
   *                                     config array to pass to a new Client instance.
   * @param string $rootUrl The root URL used for requests to the service.
   */
  public function __construct($clientOrConfig = [], $rootUrl = null)
  {
    parent::__construct($clientOrConfig);
    $this->rootUrl = $rootUrl ?: 'https://merchantapi.googleapis.com/';
    $this->rootUrlTemplate = $rootUrl ?: 'https://merchantapi.UNIVERSE_DOMAIN/';
    $this->servicePath = '';
    $this->batchPath = 'batch';
    $this->version = 'reviews_v1beta';
    $this->serviceName = 'merchantapi';

    $this->accounts_merchantReviews = new Merchant\Resource\AccountsMerchantReviews(
        $this,
        $this->serviceName,
        'merchantReviews',
        [
          'methods' => [
            'delete' => [
              'path' => 'reviews/v1beta/{+name}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'get' => [
              'path' => 'reviews/v1beta/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'insert' => [
              'path' => 'reviews/v1beta/{+parent}/merchantReviews:insert',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'dataSource' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'reviews/v1beta/{+parent}/merchantReviews',
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
            ],
          ]
        ]
    );
    $this->accounts_productReviews = new Merchant\Resource\AccountsProductReviews(
        $this,
        $this->serviceName,
        'productReviews',
        [
          'methods' => [
            'delete' => [
              'path' => 'reviews/v1beta/{+name}',
              'httpMethod' => 'DELETE',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'get' => [
              'path' => 'reviews/v1beta/{+name}',
              'httpMethod' => 'GET',
              'parameters' => [
                'name' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
              ],
            ],'insert' => [
              'path' => 'reviews/v1beta/{+parent}/productReviews:insert',
              'httpMethod' => 'POST',
              'parameters' => [
                'parent' => [
                  'location' => 'path',
                  'type' => 'string',
                  'required' => true,
                ],
                'dataSource' => [
                  'location' => 'query',
                  'type' => 'string',
                ],
              ],
            ],'list' => [
              'path' => 'reviews/v1beta/{+parent}/productReviews',
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
            ],
          ]
        ]
    );
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Merchant::class, 'Google_Service_Merchant');
