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

namespace Google\Service\PostmasterTools;

class MetricDefinition extends \Google\Model
{
  protected $baseMetricType = BaseMetric::class;
  protected $baseMetricDataType = '';
  /**
   * Optional. Optional filters to apply to the metric.
   *
   * @var string
   */
  public $filter;
  /**
   * Required. The user-defined name for this metric. This name will be used as
   * the key for this metric's value in the response.
   *
   * @var string
   */
  public $name;

  /**
   * Required. The underlying metric to query.
   *
   * @param BaseMetric $baseMetric
   */
  public function setBaseMetric(BaseMetric $baseMetric)
  {
    $this->baseMetric = $baseMetric;
  }
  /**
   * @return BaseMetric
   */
  public function getBaseMetric()
  {
    return $this->baseMetric;
  }
  /**
   * Optional. Optional filters to apply to the metric.
   *
   * @param string $filter
   */
  public function setFilter($filter)
  {
    $this->filter = $filter;
  }
  /**
   * @return string
   */
  public function getFilter()
  {
    return $this->filter;
  }
  /**
   * Required. The user-defined name for this metric. This name will be used as
   * the key for this metric's value in the response.
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MetricDefinition::class, 'Google_Service_PostmasterTools_MetricDefinition');
