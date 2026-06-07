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

class GoogleCloudContactcenterinsightsV1MetricDefinition extends \Google\Model
{
  /**
   * Output only. The user-visible name of the metric (e.g., "Containment
   * Rate").
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. The resource name of the underlying Insights primitive (e.g.,
   * Tag or QaQuestion) used to calculate this metric.
   *
   * @var string
   */
  public $sourceId;

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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1MetricDefinition::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1MetricDefinition');
