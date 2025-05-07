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

class SubscribeListingRequest extends \Google\Model
{
  protected $destinationDatasetType = DestinationDataset::class;
  protected $destinationDatasetDataType = '';
  protected $destinationPubsubSubscriptionType = DestinationPubSubSubscription::class;
  protected $destinationPubsubSubscriptionDataType = '';

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
   * @param DestinationPubSubSubscription
   */
  public function setDestinationPubsubSubscription(DestinationPubSubSubscription $destinationPubsubSubscription)
  {
    $this->destinationPubsubSubscription = $destinationPubsubSubscription;
  }
  /**
   * @return DestinationPubSubSubscription
   */
  public function getDestinationPubsubSubscription()
  {
    return $this->destinationPubsubSubscription;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SubscribeListingRequest::class, 'Google_Service_AnalyticsHub_SubscribeListingRequest');
