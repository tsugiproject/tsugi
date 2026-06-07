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

namespace Google\Service\GoogleHealthAPI;

class CreateSubscriberPayload extends \Google\Collection
{
  protected $collection_key = 'subscriberConfigs';
  protected $endpointAuthorizationType = EndpointAuthorization::class;
  protected $endpointAuthorizationDataType = '';
  /**
   * Required. The full HTTPS URI where update notifications will be sent. The
   * URI must be a valid URL and use HTTPS as the scheme. This endpoint will be
   * verified during the `CreateSubscriber` call. See CreateSubscriber RPC
   * documentation for verification details.
   *
   * @var string
   */
  public $endpointUri;
  protected $subscriberConfigsType = SubscriberConfig::class;
  protected $subscriberConfigsDataType = 'array';

  /**
   * Required. Authorization mechanism for the subscriber endpoint. The `secret`
   * within this message is crucial for endpoint verification and for securing
   * webhook notifications.
   *
   * @param EndpointAuthorization $endpointAuthorization
   */
  public function setEndpointAuthorization(EndpointAuthorization $endpointAuthorization)
  {
    $this->endpointAuthorization = $endpointAuthorization;
  }
  /**
   * @return EndpointAuthorization
   */
  public function getEndpointAuthorization()
  {
    return $this->endpointAuthorization;
  }
  /**
   * Required. The full HTTPS URI where update notifications will be sent. The
   * URI must be a valid URL and use HTTPS as the scheme. This endpoint will be
   * verified during the `CreateSubscriber` call. See CreateSubscriber RPC
   * documentation for verification details.
   *
   * @param string $endpointUri
   */
  public function setEndpointUri($endpointUri)
  {
    $this->endpointUri = $endpointUri;
  }
  /**
   * @return string
   */
  public function getEndpointUri()
  {
    return $this->endpointUri;
  }
  /**
   * Optional. Configuration for the subscriber.
   *
   * @param SubscriberConfig[] $subscriberConfigs
   */
  public function setSubscriberConfigs($subscriberConfigs)
  {
    $this->subscriberConfigs = $subscriberConfigs;
  }
  /**
   * @return SubscriberConfig[]
   */
  public function getSubscriberConfigs()
  {
    return $this->subscriberConfigs;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CreateSubscriberPayload::class, 'Google_Service_GoogleHealthAPI_CreateSubscriberPayload');
