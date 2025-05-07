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

namespace Google\Service\Css;

class HeadlineOfferInstallment extends \Google\Model
{
  protected $amountType = Price::class;
  protected $amountDataType = '';
  protected $downpaymentType = Price::class;
  protected $downpaymentDataType = '';
  /**
   * @var string
   */
  public $months;

  /**
   * @param Price
   */
  public function setAmount(Price $amount)
  {
    $this->amount = $amount;
  }
  /**
   * @return Price
   */
  public function getAmount()
  {
    return $this->amount;
  }
  /**
   * @param Price
   */
  public function setDownpayment(Price $downpayment)
  {
    $this->downpayment = $downpayment;
  }
  /**
   * @return Price
   */
  public function getDownpayment()
  {
    return $this->downpayment;
  }
  /**
   * @param string
   */
  public function setMonths($months)
  {
    $this->months = $months;
  }
  /**
   * @return string
   */
  public function getMonths()
  {
    return $this->months;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HeadlineOfferInstallment::class, 'Google_Service_Css_HeadlineOfferInstallment');
