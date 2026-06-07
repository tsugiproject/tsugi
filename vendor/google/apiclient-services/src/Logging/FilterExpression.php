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

class FilterExpression extends \Google\Model
{
  /**
   * Invalid value, do not use.
   */
  public const COMPARATOR_COMPARATOR_UNSPECIFIED = 'COMPARATOR_UNSPECIFIED';
  /**
   * The value is equal to the inputted value.
   */
  public const COMPARATOR_EQUALS = 'EQUALS';
  /**
   * The value is equal to the inputted regex value.
   */
  public const COMPARATOR_MATCHES_REGEXP = 'MATCHES_REGEXP';
  /**
   * The value is greater than the inputted value.
   */
  public const COMPARATOR_GREATER_THAN = 'GREATER_THAN';
  /**
   * The value is less than the inputted value.
   */
  public const COMPARATOR_LESS_THAN = 'LESS_THAN';
  /**
   * The value is greater than or equal to the inputted value.
   */
  public const COMPARATOR_GREATER_THAN_EQUALS = 'GREATER_THAN_EQUALS';
  /**
   * The value is less than or equal to the inputted value.
   */
  public const COMPARATOR_LESS_THAN_EQUALS = 'LESS_THAN_EQUALS';
  /**
   * Requires the filter_value to be a Value type with null_value set to true.
   */
  public const COMPARATOR_IS_NULL = 'IS_NULL';
  /**
   * The value is in the inputted array value.
   */
  public const COMPARATOR_IN = 'IN';
  /**
   * The value is like the inputted value.
   */
  public const COMPARATOR_LIKE = 'LIKE';
  /**
   * The comparison type to use for the filter.
   *
   * @var string
   */
  public $comparator;
  protected $fieldSourceType = FieldSource::class;
  protected $fieldSourceDataType = '';
  protected $fieldSourceValueType = FieldSource::class;
  protected $fieldSourceValueDataType = '';
  /**
   * Determines if the NOT flag should be added to the comparator.
   *
   * @var bool
   */
  public $isNegation;
  /**
   * The Value will be used to hold user defined constants set as the Right Hand
   * Side of the filter.
   *
   * @var array
   */
  public $literalValue;

  /**
   * The comparison type to use for the filter.
   *
   * Accepted values: COMPARATOR_UNSPECIFIED, EQUALS, MATCHES_REGEXP,
   * GREATER_THAN, LESS_THAN, GREATER_THAN_EQUALS, LESS_THAN_EQUALS, IS_NULL,
   * IN, LIKE
   *
   * @param self::COMPARATOR_* $comparator
   */
  public function setComparator($comparator)
  {
    $this->comparator = $comparator;
  }
  /**
   * @return self::COMPARATOR_*
   */
  public function getComparator()
  {
    return $this->comparator;
  }
  /**
   * Can be one of the FieldSource types: field name, alias ref, variable ref,
   * or a literal value.
   *
   * @param FieldSource $fieldSource
   */
  public function setFieldSource(FieldSource $fieldSource)
  {
    $this->fieldSource = $fieldSource;
  }
  /**
   * @return FieldSource
   */
  public function getFieldSource()
  {
    return $this->fieldSource;
  }
  /**
   * The field. This will be the field that is set as the Right Hand Side of the
   * filter.
   *
   * @param FieldSource $fieldSourceValue
   */
  public function setFieldSourceValue(FieldSource $fieldSourceValue)
  {
    $this->fieldSourceValue = $fieldSourceValue;
  }
  /**
   * @return FieldSource
   */
  public function getFieldSourceValue()
  {
    return $this->fieldSourceValue;
  }
  /**
   * Determines if the NOT flag should be added to the comparator.
   *
   * @param bool $isNegation
   */
  public function setIsNegation($isNegation)
  {
    $this->isNegation = $isNegation;
  }
  /**
   * @return bool
   */
  public function getIsNegation()
  {
    return $this->isNegation;
  }
  /**
   * The Value will be used to hold user defined constants set as the Right Hand
   * Side of the filter.
   *
   * @param array $literalValue
   */
  public function setLiteralValue($literalValue)
  {
    $this->literalValue = $literalValue;
  }
  /**
   * @return array
   */
  public function getLiteralValue()
  {
    return $this->literalValue;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FilterExpression::class, 'Google_Service_Logging_FilterExpression');
