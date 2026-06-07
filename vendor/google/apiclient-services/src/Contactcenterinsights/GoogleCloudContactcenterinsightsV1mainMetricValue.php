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

namespace Google\Service\Contactcenterinsights;

class GoogleCloudContactcenterinsightsV1mainMetricValue extends \Google\Collection
{
  /**
   * Metric type is unspecified.
   */
  public const METRIC_TYPE_METRIC_TYPE_UNSPECIFIED = 'METRIC_TYPE_UNSPECIFIED';
  /**
   * Escalation rate.
   */
  public const METRIC_TYPE_ESCALATION = 'ESCALATION';
  /**
   * Containment rate.
   */
  public const METRIC_TYPE_CONTAINMENT = 'CONTAINMENT';
  protected $collection_key = 'conversations';
  /**
   * Output only. The list of conversation names that contributed to this metric
   * (hits). Format:
   * `projects/{project}/locations/{location}/conversations/{conversation}`
   *
   * @var string[]
   */
  public $conversations;
  /**
   * Output only. The user-visible name of the metric (e.g., "Containment
   * Rate").
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. The number of positive matches (hits) for this metric.
   *
   * @var int
   */
  public $hitCount;
  /**
   * Output only. Deprecated: The type of the metric. Metrics for Outcome Based
   * Insights derived from QueryMetrics.
   *
   * @var string
   */
  public $metricType;
  /**
   * Output only. The resource name of the underlying Insights primitive (e.g.,
   * Tag or QaQuestion) used to calculate this metric.
   *
   * @var string
   */
  public $sourceId;
  /**
   * Output only. The total number of items evaluated for this metric.
   *
   * @var int
   */
  public $totalCount;
  /**
   * Output only. The calculated value of the metric (usually a ratio or rate
   * 0.0 - 1.0).
   *
   * @var 
   */
  public $value;

  /**
   * Output only. The list of conversation names that contributed to this metric
   * (hits). Format:
   * `projects/{project}/locations/{location}/conversations/{conversation}`
   *
   * @param string[] $conversations
   */
  public function setConversations($conversations)
  {
    $this->conversations = $conversations;
  }
  /**
   * @return string[]
   */
  public function getConversations()
  {
    return $this->conversations;
  }
  /**
   * Output only. The user-visible name of the metric (e.g., "Containment
   * Rate").
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Output only. The number of positive matches (hits) for this metric.
   *
   * @param int $hitCount
   */
  public function setHitCount($hitCount)
  {
    $this->hitCount = $hitCount;
  }
  /**
   * @return int
   */
  public function getHitCount()
  {
    return $this->hitCount;
  }
  /**
   * Output only. Deprecated: The type of the metric. Metrics for Outcome Based
   * Insights derived from QueryMetrics.
   *
   * Accepted values: METRIC_TYPE_UNSPECIFIED, ESCALATION, CONTAINMENT
   *
   * @param self::METRIC_TYPE_* $metricType
   */
  public function setMetricType($metricType)
  {
    $this->metricType = $metricType;
  }
  /**
   * @return self::METRIC_TYPE_*
   */
  public function getMetricType()
  {
    return $this->metricType;
  }
  /**
   * Output only. The resource name of the underlying Insights primitive (e.g.,
   * Tag or QaQuestion) used to calculate this metric.
   *
   * @param string $sourceId
   */
  public function setSourceId($sourceId)
  {
    $this->sourceId = $sourceId;
  }
  /**
   * @return string
   */
  public function getSourceId()
  {
    return $this->sourceId;
  }
  /**
   * Output only. The total number of items evaluated for this metric.
   *
   * @param int $totalCount
   */
  public function setTotalCount($totalCount)
  {
    $this->totalCount = $totalCount;
  }
  /**
   * @return int
   */
  public function getTotalCount()
  {
    return $this->totalCount;
  }
  public function setValue($value)
  {
    $this->value = $value;
  }
  public function getValue()
  {
    return $this->value;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1mainMetricValue::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1mainMetricValue');
