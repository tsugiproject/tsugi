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

namespace Google\Service\BigtableAdmin;

class GoogleBigtableAdminV2TypeAggregate extends \Google\Model
{
  protected $hllppUniqueCountType = GoogleBigtableAdminV2TypeAggregateHyperLogLogPlusPlusUniqueCount::class;
  protected $hllppUniqueCountDataType = '';
  protected $inputTypeType = Type::class;
  protected $inputTypeDataType = '';
  protected $maxType = GoogleBigtableAdminV2TypeAggregateMax::class;
  protected $maxDataType = '';
  protected $minType = GoogleBigtableAdminV2TypeAggregateMin::class;
  protected $minDataType = '';
  protected $stateTypeType = Type::class;
  protected $stateTypeDataType = '';
  protected $sumType = GoogleBigtableAdminV2TypeAggregateSum::class;
  protected $sumDataType = '';

  /**
   * @param GoogleBigtableAdminV2TypeAggregateHyperLogLogPlusPlusUniqueCount
   */
  public function setHllppUniqueCount(GoogleBigtableAdminV2TypeAggregateHyperLogLogPlusPlusUniqueCount $hllppUniqueCount)
  {
    $this->hllppUniqueCount = $hllppUniqueCount;
  }
  /**
   * @return GoogleBigtableAdminV2TypeAggregateHyperLogLogPlusPlusUniqueCount
   */
  public function getHllppUniqueCount()
  {
    return $this->hllppUniqueCount;
  }
  /**
   * @param Type
   */
  public function setInputType(Type $inputType)
  {
    $this->inputType = $inputType;
  }
  /**
   * @return Type
   */
  public function getInputType()
  {
    return $this->inputType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeAggregateMax
   */
  public function setMax(GoogleBigtableAdminV2TypeAggregateMax $max)
  {
    $this->max = $max;
  }
  /**
   * @return GoogleBigtableAdminV2TypeAggregateMax
   */
  public function getMax()
  {
    return $this->max;
  }
  /**
   * @param GoogleBigtableAdminV2TypeAggregateMin
   */
  public function setMin(GoogleBigtableAdminV2TypeAggregateMin $min)
  {
    $this->min = $min;
  }
  /**
   * @return GoogleBigtableAdminV2TypeAggregateMin
   */
  public function getMin()
  {
    return $this->min;
  }
  /**
   * @param Type
   */
  public function setStateType(Type $stateType)
  {
    $this->stateType = $stateType;
  }
  /**
   * @return Type
   */
  public function getStateType()
  {
    return $this->stateType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeAggregateSum
   */
  public function setSum(GoogleBigtableAdminV2TypeAggregateSum $sum)
  {
    $this->sum = $sum;
  }
  /**
   * @return GoogleBigtableAdminV2TypeAggregateSum
   */
  public function getSum()
  {
    return $this->sum;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleBigtableAdminV2TypeAggregate::class, 'Google_Service_BigtableAdmin_GoogleBigtableAdminV2TypeAggregate');
