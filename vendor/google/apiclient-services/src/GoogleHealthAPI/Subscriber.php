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

class Subscriber extends \Google\Collection
{
  /**
   * Represents an unspecified subscriber state.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * Represents an unverified subscriber. This is the initial state of the
   * subscriber when it is created. The backend will verify the subscriber's
   * endpoint_uri.
   */
  public const STATE_UNVERIFIED = 'UNVERIFIED';
  /**
   * Represents an active subscriber. The endpoint has been verified.
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * Represents an inactive subscriber.
   */
  public const STATE_INACTIVE = 'INACTIVE';
  protected $collection_key = 'subscriberConfigs';
  /**
   * Output only. The time at which the subscriber was created.
   *
   * @var string
   */
  public $createTime;
  protected $endpointAuthorizationType = EndpointAuthorization::class;
  protected $endpointAuthorizationDataType = '';
  /**
   * Required. The full HTTPS URI where update notifications will be sent. The
   * URI must be a valid URL and use HTTPS as the scheme. This endpoint will be
   * verified during CreateSubscriber and UpdateSubscriber calls. See RPC
   * documentation for verification details.
   *
   * @var string
   */
  public $endpointUri;
  /**
   * Identifier. The resource name of the Subscriber. Format:
   * projects/{project}/subscribers/{subscriber} The {project} ID is a Google
   * Cloud Project ID or Project Number. The {subscriber} ID is user-settable
   * (4-36 characters, matching /[a-z]([a-z0-9-]{2,34}[a-z0-9])/) if provided
   * during creation, or system-generated otherwise (e.g., a UUID). Example
   * (User-settable subscriber ID): projects/my-project/subscribers/my-sub-123
   * Example (System-generated subscriber ID): projects/my-
   * project/subscribers/a1b2c3d4-e5f6-7890-1234-567890abcdef
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The state of the subscriber.
   *
   * @var string
   */
  public $state;
  protected $subscriberConfigsType = SubscriberConfig::class;
  protected $subscriberConfigsDataType = 'array';
  /**
   * Output only. The time at which the subscriber was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. The time at which the subscriber was created.
   *
   * @param string $createTime
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * Required. Authorization mechanism for a subscriber endpoint. This is
   * required to ensure the endpoint can be verified.
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
   * verified during CreateSubscriber and UpdateSubscriber calls. See RPC
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
   * Identifier. The resource name of the Subscriber. Format:
   * projects/{project}/subscribers/{subscriber} The {project} ID is a Google
   * Cloud Project ID or Project Number. The {subscriber} ID is user-settable
   * (4-36 characters, matching /[a-z]([a-z0-9-]{2,34}[a-z0-9])/) if provided
   * during creation, or system-generated otherwise (e.g., a UUID). Example
   * (User-settable subscriber ID): projects/my-project/subscribers/my-sub-123
   * Example (System-generated subscriber ID): projects/my-
   * project/subscribers/a1b2c3d4-e5f6-7890-1234-567890abcdef
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Output only. The state of the subscriber.
   *
   * Accepted values: STATE_UNSPECIFIED, UNVERIFIED, ACTIVE, INACTIVE
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
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
  /**
   * Output only. The time at which the subscriber was last updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Subscriber::class, 'Google_Service_GoogleHealthAPI_Subscriber');
