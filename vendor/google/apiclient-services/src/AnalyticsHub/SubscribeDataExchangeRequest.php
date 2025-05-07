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

namespace Google\Service\AnalyticsHub;

class SubscribeDataExchangeRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $destination;
  protected $destinationDatasetType = DestinationDataset::class;
  protected $destinationDatasetDataType = '';
  /**
   * @var string
   */
  public $subscriberContact;
  /**
   * @var string
   */
  public $subscription;

  /**
   * @param string
   */
  public function setDestination($destination)
  {
    $this->destination = $destination;
  }
  /**
   * @return string
   */
  public function getDestination()
  {
    return $this->destination;
  }
  /**
   * @param DestinationDataset
   */
  public function setDestinationDataset(DestinationDataset $destinationDataset)
  {
    $this->destinationDataset = $destinationDataset;
  }
  /**
   * @return DestinationDataset
   */
  public function getDestinationDataset()
  {
    return $this->destinationDataset;
  }
  /**
   * @param string
   */
  public function setSubscriberContact($subscriberContact)
  {
    $this->subscriberContact = $subscriberContact;
  }
  /**
   * @return string
   */
  public function getSubscriberContact()
  {
    return $this->subscriberContact;
  }
  /**
   * @param string
   */
  public function setSubscription($subscription)
  {
    $this->subscription = $subscription;
  }
  /**
   * @return string
   */
  public function getSubscription()
  {
    return $this->subscription;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SubscribeDataExchangeRequest::class, 'Google_Service_AnalyticsHub_SubscribeDataExchangeRequest');
