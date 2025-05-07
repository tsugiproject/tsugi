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

class HeadlineOfferSubscriptionCost extends \Google\Model
{
  protected $amountType = Price::class;
  protected $amountDataType = '';
  /**
   * @var string
   */
  public $period;
  /**
   * @var string
   */
  public $periodLength;

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
   * @param string
   */
  public function setPeriod($period)
  {
    $this->period = $period;
  }
  /**
   * @return string
   */
  public function getPeriod()
  {
    return $this->period;
  }
  /**
   * @param string
   */
  public function setPeriodLength($periodLength)
  {
    $this->periodLength = $periodLength;
  }
  /**
   * @return string
   */
  public function getPeriodLength()
  {
    return $this->periodLength;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HeadlineOfferSubscriptionCost::class, 'Google_Service_Css_HeadlineOfferSubscriptionCost');
