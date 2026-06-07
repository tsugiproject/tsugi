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

class DomainStat extends \Google\Model
{
  protected $dateType = Date::class;
  protected $dateDataType = '';
  /**
   * The user-defined name from MetricDefinition.name in the request, used to
   * correlate this result with the requested metric.
   *
   * @var string
   */
  public $metric;
  /**
   * Output only. The resource name of the DomainStat resource. Format:
   * domains/{domain}/domainStats/{domain_stat} The `{domain_stat}` segment is
   * an opaque, server-generated ID. We recommend using the `metric` field to
   * identify queried metrics instead of parsing the name.
   *
   * @var string
   */
  public $name;
  protected $valueType = StatisticValue::class;
  protected $valueDataType = '';

  /**
   * Optional. The specific date for these stats, if granularity is DAILY. This
   * field is populated if the QueryDomainStatsRequest specified a DAILY
   * aggregation granularity.
   *
   * @param Date $date
   */
  public function setDate(Date $date)
  {
    $this->date = $date;
  }
  /**
   * @return Date
   */
  public function getDate()
  {
    return $this->date;
  }
  /**
   * The user-defined name from MetricDefinition.name in the request, used to
   * correlate this result with the requested metric.
   *
   * @param string $metric
   */
  public function setMetric($metric)
  {
    $this->metric = $metric;
  }
  /**
   * @return string
   */
  public function getMetric()
  {
    return $this->metric;
  }
  /**
   * Output only. The resource name of the DomainStat resource. Format:
   * domains/{domain}/domainStats/{domain_stat} The `{domain_stat}` segment is
   * an opaque, server-generated ID. We recommend using the `metric` field to
   * identify queried metrics instead of parsing the name.
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
  /**
   * The value of the corresponding metric.
   *
   * @param StatisticValue $value
   */
  public function setValue(StatisticValue $value)
  {
    $this->value = $value;
  }
  /**
   * @return StatisticValue
   */
  public function getValue()
  {
    return $this->value;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DomainStat::class, 'Google_Service_PostmasterTools_DomainStat');
