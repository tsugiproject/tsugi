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

class FunctionApplication extends \Google\Collection
{
  protected $collection_key = 'parameters';
  /**
   * Optional. Parameters to be applied to the aggregation. Aggregations that
   * support or require parameters are listed above.
   *
   * @var array[]
   */
  public $parameters;
  /**
   * Required. Specifies the aggregation function. Use one of the following
   * string identifiers: "average": Computes the average (AVG). Applies only to
   * numeric values. "count": Counts the number of values (COUNT). "count-
   * distinct": Counts the number of distinct values (COUNT DISTINCT). "count-
   * distinct-approx": Approximates the count of distinct values
   * (APPROX_COUNT_DISTINCT). "max": Finds the maximum value (MAX). Applies only
   * to numeric values. "min": Finds the minimum value (MIN). Applies only to
   * numeric values. "sum": Computes the sum (SUM). Applies only to numeric
   * values.
   *
   * @var string
   */
  public $type;

  /**
   * Optional. Parameters to be applied to the aggregation. Aggregations that
   * support or require parameters are listed above.
   *
   * @param array[] $parameters
   */
  public function setParameters($parameters)
  {
    $this->parameters = $parameters;
  }
  /**
   * @return array[]
   */
  public function getParameters()
  {
    return $this->parameters;
  }
  /**
   * Required. Specifies the aggregation function. Use one of the following
   * string identifiers: "average": Computes the average (AVG). Applies only to
   * numeric values. "count": Counts the number of values (COUNT). "count-
   * distinct": Counts the number of distinct values (COUNT DISTINCT). "count-
   * distinct-approx": Approximates the count of distinct values
   * (APPROX_COUNT_DISTINCT). "max": Finds the maximum value (MAX). Applies only
   * to numeric values. "min": Finds the minimum value (MIN). Applies only to
   * numeric values. "sum": Computes the sum (SUM). Applies only to numeric
   * values.
   *
   * @param string $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FunctionApplication::class, 'Google_Service_Logging_FunctionApplication');
