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

namespace Google\Service\DataManager;

class Item extends \Google\Collection
{
  protected $collection_key = 'customVariables';
  protected $additionalItemParametersType = ItemParameter::class;
  protected $additionalItemParametersDataType = 'array';
  /**
   * Optional. The conversion value associated with this item within the event,
   * for cases where the conversion value is different for each item.
   *
   * @var 
   */
  public $conversionValue;
  protected $customVariablesType = ItemCustomVariable::class;
  protected $customVariablesDataType = 'array';
  /**
   * Optional. A unique identifier to reference the item.
   *
   * @var string
   */
  public $itemId;
  /**
   * Optional. The feed label of the Merchant Center feed. If countries are
   * still being used, the 2-letter country code in ISO-3166-1 alpha-2 can be
   * used instead. For Store Sales events this will override the value set at
   * the cart level. This field is ignored for other events.
   *
   * @var string
   */
  public $merchantFeedLabel;
  /**
   * Optional. The language code in ISO 639-1 associated with the Merchant
   * Center feed where your items are uploaded.
   *
   * @var string
   */
  public $merchantFeedLanguageCode;
  /**
   * Optional. The Merchant Center ID associated with the item. For Store Sales
   * events this will override the value set at the cart level. This field is
   * ignored for other events.
   *
   * @var string
   */
  public $merchantId;
  /**
   * Optional. The product ID within the Merchant Center account.
   *
   * @var string
   */
  public $merchantProductId;
  /**
   * Optional. The number of this item associated with the event.
   *
   * @var string
   */
  public $quantity;
  /**
   * Optional. The unit price excluding tax, shipping, and any transaction level
   * discounts.
   *
   * @var 
   */
  public $unitPrice;

  /**
   * Optional. A bucket of any [event parameters related to an item](https://dev
   * elopers.google.com/analytics/devguides/collection/protocol/ga4/reference/ev
   * ents) to be included within the event that were not already specified using
   * other structured fields.
   *
   * @param ItemParameter[] $additionalItemParameters
   */
  public function setAdditionalItemParameters($additionalItemParameters)
  {
    $this->additionalItemParameters = $additionalItemParameters;
  }
  /**
   * @return ItemParameter[]
   */
  public function getAdditionalItemParameters()
  {
    return $this->additionalItemParameters;
  }
  public function setConversionValue($conversionValue)
  {
    $this->conversionValue = $conversionValue;
  }
  public function getConversionValue()
  {
    return $this->conversionValue;
  }
  /**
   * Optional. Additional key/value pair information to send to the conversion
   * containers (conversion action or Floodlight activity), when tracking per-
   * item conversions.
   *
   * @param ItemCustomVariable[] $customVariables
   */
  public function setCustomVariables($customVariables)
  {
    $this->customVariables = $customVariables;
  }
  /**
   * @return ItemCustomVariable[]
   */
  public function getCustomVariables()
  {
    return $this->customVariables;
  }
  /**
   * Optional. A unique identifier to reference the item.
   *
   * @param string $itemId
   */
  public function setItemId($itemId)
  {
    $this->itemId = $itemId;
  }
  /**
   * @return string
   */
  public function getItemId()
  {
    return $this->itemId;
  }
  /**
   * Optional. The feed label of the Merchant Center feed. If countries are
   * still being used, the 2-letter country code in ISO-3166-1 alpha-2 can be
   * used instead. For Store Sales events this will override the value set at
   * the cart level. This field is ignored for other events.
   *
   * @param string $merchantFeedLabel
   */
  public function setMerchantFeedLabel($merchantFeedLabel)
  {
    $this->merchantFeedLabel = $merchantFeedLabel;
  }
  /**
   * @return string
   */
  public function getMerchantFeedLabel()
  {
    return $this->merchantFeedLabel;
  }
  /**
   * Optional. The language code in ISO 639-1 associated with the Merchant
   * Center feed where your items are uploaded.
   *
   * @param string $merchantFeedLanguageCode
   */
  public function setMerchantFeedLanguageCode($merchantFeedLanguageCode)
  {
    $this->merchantFeedLanguageCode = $merchantFeedLanguageCode;
  }
  /**
   * @return string
   */
  public function getMerchantFeedLanguageCode()
  {
    return $this->merchantFeedLanguageCode;
  }
  /**
   * Optional. The Merchant Center ID associated with the item. For Store Sales
   * events this will override the value set at the cart level. This field is
   * ignored for other events.
   *
   * @param string $merchantId
   */
  public function setMerchantId($merchantId)
  {
    $this->merchantId = $merchantId;
  }
  /**
   * @return string
   */
  public function getMerchantId()
  {
    return $this->merchantId;
  }
  /**
   * Optional. The product ID within the Merchant Center account.
   *
   * @param string $merchantProductId
   */
  public function setMerchantProductId($merchantProductId)
  {
    $this->merchantProductId = $merchantProductId;
  }
  /**
   * @return string
   */
  public function getMerchantProductId()
  {
    return $this->merchantProductId;
  }
  /**
   * Optional. The number of this item associated with the event.
   *
   * @param string $quantity
   */
  public function setQuantity($quantity)
  {
    $this->quantity = $quantity;
  }
  /**
   * @return string
   */
  public function getQuantity()
  {
    return $this->quantity;
  }
  public function setUnitPrice($unitPrice)
  {
    $this->unitPrice = $unitPrice;
  }
  public function getUnitPrice()
  {
    return $this->unitPrice;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Item::class, 'Google_Service_DataManager_Item');
