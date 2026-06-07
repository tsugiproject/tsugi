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

class QueryDomainStatsRequest extends \Google\Collection
{
  /**
   * Unspecified granularity. Defaults to DAILY.
   */
  public const AGGREGATION_GRANULARITY_AGGREGATION_GRANULARITY_UNSPECIFIED = 'AGGREGATION_GRANULARITY_UNSPECIFIED';
  /**
   * Statistics are aggregated on a daily basis. Each DomainStats entry in the
   * response will correspond to a single day.
   */
  public const AGGREGATION_GRANULARITY_DAILY = 'DAILY';
  /**
   * Statistics are aggregated over the entire requested time period. Each
   * DomainStats entry in the response will represent the total for the period.
   */
  public const AGGREGATION_GRANULARITY_OVERALL = 'OVERALL';
  protected $collection_key = 'metricDefinitions';
  /**
   * Optional. The granularity at which to aggregate the statistics. If
   * unspecified, defaults to DAILY.
   *
   * @var string
   */
  public $aggregationGranularity;
  protected $metricDefinitionsType = MetricDefinition::class;
  protected $metricDefinitionsDataType = 'array';
  /**
   * Optional. The maximum number of DomainStats resources to return in the
   * response. The server may return fewer than this value. If unspecified, a
   * default value of 10 will be used. The maximum value is 200.
   *
   * @var int
   */
  public $pageSize;
  /**
   * Optional. The next_page_token value returned from a previous List request,
   * if any. If the aggregation granularity is DAILY, the page token will be the
   * encoded date + "/" + metric name. If the aggregation granularity is
   * OVERALL, the page token will be the encoded metric name.
   *
   * @var string
   */
  public $pageToken;
  /**
   * Required. The parent resource name where the stats are queried. Format:
   * domains/{domain}
   *
   * @var string
   */
  public $parent;
  protected $timeQueryType = TimeQuery::class;
  protected $timeQueryDataType = '';

  /**
   * Optional. The granularity at which to aggregate the statistics. If
   * unspecified, defaults to DAILY.
   *
   * Accepted values: AGGREGATION_GRANULARITY_UNSPECIFIED, DAILY, OVERALL
   *
   * @param self::AGGREGATION_GRANULARITY_* $aggregationGranularity
   */
  public function setAggregationGranularity($aggregationGranularity)
  {
    $this->aggregationGranularity = $aggregationGranularity;
  }
  /**
   * @return self::AGGREGATION_GRANULARITY_*
   */
  public function getAggregationGranularity()
  {
    return $this->aggregationGranularity;
  }
  /**
   * Required. The specific metrics to query. You can define a custom name for
   * each metric, which will be used in the response.
   *
   * @param MetricDefinition[] $metricDefinitions
   */
  public function setMetricDefinitions($metricDefinitions)
  {
    $this->metricDefinitions = $metricDefinitions;
  }
  /**
   * @return MetricDefinition[]
   */
  public function getMetricDefinitions()
  {
    return $this->metricDefinitions;
  }
  /**
   * Optional. The maximum number of DomainStats resources to return in the
   * response. The server may return fewer than this value. If unspecified, a
   * default value of 10 will be used. The maximum value is 200.
   *
   * @param int $pageSize
   */
  public function setPageSize($pageSize)
  {
    $this->pageSize = $pageSize;
  }
  /**
   * @return int
   */
  public function getPageSize()
  {
    return $this->pageSize;
  }
  /**
   * Optional. The next_page_token value returned from a previous List request,
   * if any. If the aggregation granularity is DAILY, the page token will be the
   * encoded date + "/" + metric name. If the aggregation granularity is
   * OVERALL, the page token will be the encoded metric name.
   *
   * @param string $pageToken
   */
  public function setPageToken($pageToken)
  {
    $this->pageToken = $pageToken;
  }
  /**
   * @return string
   */
  public function getPageToken()
  {
    return $this->pageToken;
  }
  /**
   * Required. The parent resource name where the stats are queried. Format:
   * domains/{domain}
   *
   * @param string $parent
   */
  public function setParent($parent)
  {
    $this->parent = $parent;
  }
  /**
   * @return string
   */
  public function getParent()
  {
    return $this->parent;
  }
  /**
   * Required. The time range or specific dates for which to retrieve the
   * metrics.
   *
   * @param TimeQuery $timeQuery
   */
  public function setTimeQuery(TimeQuery $timeQuery)
  {
    $this->timeQuery = $timeQuery;
  }
  /**
   * @return TimeQuery
   */
  public function getTimeQuery()
  {
    return $this->timeQuery;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(QueryDomainStatsRequest::class, 'Google_Service_PostmasterTools_QueryDomainStatsRequest');
