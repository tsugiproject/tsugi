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

namespace Google\Service\Logging;

class FilterPredicate extends \Google\Collection
{
  /**
   * Invalid value, do not use.
   */
  public const OPERATOR_TYPE_OPERATOR_TYPE_UNSPECIFIED = 'OPERATOR_TYPE_UNSPECIFIED';
  /**
   * AND will be the default operator type.
   */
  public const OPERATOR_TYPE_AND = 'AND';
  /**
   * OR operator type.
   */
  public const OPERATOR_TYPE_OR = 'OR';
  /**
   * LEAF operator type.
   */
  public const OPERATOR_TYPE_LEAF = 'LEAF';
  protected $collection_key = 'childPredicates';
  protected $childPredicatesType = FilterPredicate::class;
  protected $childPredicatesDataType = 'array';
  protected $leafPredicateType = FilterExpression::class;
  protected $leafPredicateDataType = '';
  /**
   * The operator type for the filter. Currently there is no support for
   * multiple levels of nesting, so this will be a single value with no joining
   * of different operator types
   *
   * @var string
   */
  public $operatorType;

  /**
   * The children of the filter predicate. This equates to the branches of the
   * filter predicate that could contain further nested leaves.
   *
   * @param FilterPredicate[] $childPredicates
   */
  public function setChildPredicates($childPredicates)
  {
    $this->childPredicates = $childPredicates;
  }
  /**
   * @return FilterPredicate[]
   */
  public function getChildPredicates()
  {
    return $this->childPredicates;
  }
  /**
   * The leaves of the filter predicate. This equates to the last leaves of the
   * filter predicate associated with an operator.
   *
   * @param FilterExpression $leafPredicate
   */
  public function setLeafPredicate(FilterExpression $leafPredicate)
  {
    $this->leafPredicate = $leafPredicate;
  }
  /**
   * @return FilterExpression
   */
  public function getLeafPredicate()
  {
    return $this->leafPredicate;
  }
  /**
   * The operator type for the filter. Currently there is no support for
   * multiple levels of nesting, so this will be a single value with no joining
   * of different operator types
   *
   * Accepted values: OPERATOR_TYPE_UNSPECIFIED, AND, OR, LEAF
   *
   * @param self::OPERATOR_TYPE_* $operatorType
   */
  public function setOperatorType($operatorType)
  {
    $this->operatorType = $operatorType;
  }
  /**
   * @return self::OPERATOR_TYPE_*
   */
  public function getOperatorType()
  {
    return $this->operatorType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FilterPredicate::class, 'Google_Service_Logging_FilterPredicate');
