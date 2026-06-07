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

class ProjectedField extends \Google\Model
{
  /**
   * Invalid value. Operation must be specified.
   */
  public const OPERATION_FIELD_OPERATION_UNSPECIFIED = 'FIELD_OPERATION_UNSPECIFIED';
  /**
   * Select the field directly without grouping or aggregation. Corresponds to
   * including the raw field (potentially with cast, regex, or alias) in the
   * SELECT list.
   */
  public const OPERATION_NO_SETTING = 'NO_SETTING';
  /**
   * Group the query results by the distinct values of this field. Corresponds
   * to including the field (potentially truncated) in the GROUP BY clause.
   */
  public const OPERATION_GROUP_BY = 'GROUP_BY';
  /**
   * Apply an aggregation function to this field across grouped results.
   * Corresponds to applying a function like COUNT, SUM, AVG in the SELECT list.
   * Requires sql_aggregation_function to be set.
   */
  public const OPERATION_AGGREGATE = 'AGGREGATE';
  /**
   * The alias name for the field. Valid alias examples are: - single word
   * alias: TestAlias - numbers in an alias: Alias123 - multi word alias should
   * be enclosed in quotes: "Test Alias" Invalid alias examples are: - alias
   * containing keywords: WHERE, SELECT, FROM, etc. - alias starting with a
   * number: 1stAlias
   *
   * @var string
   */
  public $alias;
  /**
   * The cast for the field. This can any SQL cast type. Examples: - STRING -
   * CHAR - DATE - TIMESTAMP - DATETIME - INT - FLOAT
   *
   * @var string
   */
  public $cast;
  /**
   * Optional. The field name. This will be the field that is selected using the
   * dot notation to display the drill down value.
   *
   * @var string
   */
  public $field;
  /**
   * Specifies the role of this field (direct selection, grouping, or
   * aggregation).
   *
   * @var string
   */
  public $operation;
  /**
   * The re2 extraction for the field. This will be used to extract the value
   * from the field using REGEXP_EXTRACT. More information on re2 can be found
   * here: https://github.com/google/re2/wiki/Syntax. Meta characters like +?()|
   * will need to be escaped. Examples: - ".(autoscaler.*)$" will be converted
   * to REGEXP_EXTRACT(JSON_VALUE(field),"request(.*(autoscaler.*)$)")in SQL. -
   * "\(test_value\)$" will be converted to
   * REGEXP_EXTRACT(JSON_VALUE(field),"request(\(test_value\)$)") in SQL.
   *
   * @var string
   */
  public $regexExtraction;
  protected $sqlAggregationFunctionType = FunctionApplication::class;
  protected $sqlAggregationFunctionDataType = '';
  /**
   * The truncation granularity when grouping by a time/date field. This will be
   * used to truncate the field to the granularity specified. This can be either
   * a date or a time granularity found at
   * https://cloud.google.com/bigquery/docs/reference/standard-
   * sql/timestamp_functions#timestamp_trunc_granularity_date and
   * https://cloud.google.com/bigquery/docs/reference/standard-
   * sql/timestamp_functions#timestamp_trunc_granularity_time respectively.
   *
   * @var string
   */
  public $truncationGranularity;
  protected $virtualFieldType = VirtualField::class;
  protected $virtualFieldDataType = '';

  /**
   * The alias name for the field. Valid alias examples are: - single word
   * alias: TestAlias - numbers in an alias: Alias123 - multi word alias should
   * be enclosed in quotes: "Test Alias" Invalid alias examples are: - alias
   * containing keywords: WHERE, SELECT, FROM, etc. - alias starting with a
   * number: 1stAlias
   *
   * @param string $alias
   */
  public function setAlias($alias)
  {
    $this->alias = $alias;
  }
  /**
   * @return string
   */
  public function getAlias()
  {
    return $this->alias;
  }
  /**
   * The cast for the field. This can any SQL cast type. Examples: - STRING -
   * CHAR - DATE - TIMESTAMP - DATETIME - INT - FLOAT
   *
   * @param string $cast
   */
  public function setCast($cast)
  {
    $this->cast = $cast;
  }
  /**
   * @return string
   */
  public function getCast()
  {
    return $this->cast;
  }
  /**
   * Optional. The field name. This will be the field that is selected using the
   * dot notation to display the drill down value.
   *
   * @param string $field
   */
  public function setField($field)
  {
    $this->field = $field;
  }
  /**
   * @return string
   */
  public function getField()
  {
    return $this->field;
  }
  /**
   * Specifies the role of this field (direct selection, grouping, or
   * aggregation).
   *
   * Accepted values: FIELD_OPERATION_UNSPECIFIED, NO_SETTING, GROUP_BY,
   * AGGREGATE
   *
   * @param self::OPERATION_* $operation
   */
  public function setOperation($operation)
  {
    $this->operation = $operation;
  }
  /**
   * @return self::OPERATION_*
   */
  public function getOperation()
  {
    return $this->operation;
  }
  /**
   * The re2 extraction for the field. This will be used to extract the value
   * from the field using REGEXP_EXTRACT. More information on re2 can be found
   * here: https://github.com/google/re2/wiki/Syntax. Meta characters like +?()|
   * will need to be escaped. Examples: - ".(autoscaler.*)$" will be converted
   * to REGEXP_EXTRACT(JSON_VALUE(field),"request(.*(autoscaler.*)$)")in SQL. -
   * "\(test_value\)$" will be converted to
   * REGEXP_EXTRACT(JSON_VALUE(field),"request(\(test_value\)$)") in SQL.
   *
   * @param string $regexExtraction
   */
  public function setRegexExtraction($regexExtraction)
  {
    $this->regexExtraction = $regexExtraction;
  }
  /**
   * @return string
   */
  public function getRegexExtraction()
  {
    return $this->regexExtraction;
  }
  /**
   * The function to apply to the field.
   *
   * @param FunctionApplication $sqlAggregationFunction
   */
  public function setSqlAggregationFunction(FunctionApplication $sqlAggregationFunction)
  {
    $this->sqlAggregationFunction = $sqlAggregationFunction;
  }
  /**
   * @return FunctionApplication
   */
  public function getSqlAggregationFunction()
  {
    return $this->sqlAggregationFunction;
  }
  /**
   * The truncation granularity when grouping by a time/date field. This will be
   * used to truncate the field to the granularity specified. This can be either
   * a date or a time granularity found at
   * https://cloud.google.com/bigquery/docs/reference/standard-
   * sql/timestamp_functions#timestamp_trunc_granularity_date and
   * https://cloud.google.com/bigquery/docs/reference/standard-
   * sql/timestamp_functions#timestamp_trunc_granularity_time respectively.
   *
   * @param string $truncationGranularity
   */
  public function setTruncationGranularity($truncationGranularity)
  {
    $this->truncationGranularity = $truncationGranularity;
  }
  /**
   * @return string
   */
  public function getTruncationGranularity()
  {
    return $this->truncationGranularity;
  }
  /**
   * Optional. A virtual field definition, used in place of field to define a
   * field that is computed from other fields rather than being directly present
   * in the data schema.For example, a virtual field can be defined using
   * COALESCE to select the first non-null value from a list of fields.If
   * virtual_field is set, field must not be set.
   *
   * @param VirtualField $virtualField
   */
  public function setVirtualField(VirtualField $virtualField)
  {
    $this->virtualField = $virtualField;
  }
  /**
   * @return VirtualField
   */
  public function getVirtualField()
  {
    return $this->virtualField;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectedField::class, 'Google_Service_Logging_ProjectedField');
