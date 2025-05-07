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

namespace Google\Service\Cloudchannel;

class GoogleCloudChannelV1PurchasableOffer extends \Google\Model
{
  protected $offerType = GoogleCloudChannelV1Offer::class;
  protected $offerDataType = '';
  /**
   * @var string
   */
  public $priceReferenceId;

  /**
   * @param GoogleCloudChannelV1Offer
   */
  public function setOffer(GoogleCloudChannelV1Offer $offer)
  {
    $this->offer = $offer;
  }
  /**
   * @return GoogleCloudChannelV1Offer
   */
  public function getOffer()
  {
    return $this->offer;
  }
  /**
   * @param string
   */
  public function setPriceReferenceId($priceReferenceId)
  {
    $this->priceReferenceId = $priceReferenceId;
  }
  /**
   * @return string
   */
  public function getPriceReferenceId()
  {
    return $this->priceReferenceId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudChannelV1PurchasableOffer::class, 'Google_Service_Cloudchannel_GoogleCloudChannelV1PurchasableOffer');
