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

namespace Google\Service\DiscoveryEngine;

class GoogleMonitoringV3TimeSeries extends \Google\Collection
{
  protected $collection_key = 'points';
  /**
   * @var string
   */
  public $description;
  protected $metadataType = GoogleApiMonitoredResourceMetadata::class;
  protected $metadataDataType = '';
  protected $metricType = GoogleApiMetric::class;
  protected $metricDataType = '';
  /**
   * @var string
   */
  public $metricKind;
  protected $pointsType = GoogleMonitoringV3Point::class;
  protected $pointsDataType = 'array';
  protected $resourceType = GoogleApiMonitoredResource::class;
  protected $resourceDataType = '';
  /**
   * @var string
   */
  public $unit;
  /**
   * @var string
   */
  public $valueType;

  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param GoogleApiMonitoredResourceMetadata
   */
  public function setMetadata(GoogleApiMonitoredResourceMetadata $metadata)
  {
    $this->metadata = $metadata;
  }
  /**
   * @return GoogleApiMonitoredResourceMetadata
   */
  public function getMetadata()
  {
    return $this->metadata;
  }
  /**
   * @param GoogleApiMetric
   */
  public function setMetric(GoogleApiMetric $metric)
  {
    $this->metric = $metric;
  }
  /**
   * @return GoogleApiMetric
   */
  public function getMetric()
  {
    return $this->metric;
  }
  /**
   * @param string
   */
  public function setMetricKind($metricKind)
  {
    $this->metricKind = $metricKind;
  }
  /**
   * @return string
   */
  public function getMetricKind()
  {
    return $this->metricKind;
  }
  /**
   * @param GoogleMonitoringV3Point[]
   */
  public function setPoints($points)
  {
    $this->points = $points;
  }
  /**
   * @return GoogleMonitoringV3Point[]
   */
  public function getPoints()
  {
    return $this->points;
  }
  /**
   * @param GoogleApiMonitoredResource
   */
  public function setResource(GoogleApiMonitoredResource $resource)
  {
    $this->resource = $resource;
  }
  /**
   * @return GoogleApiMonitoredResource
   */
  public function getResource()
  {
    return $this->resource;
  }
  /**
   * @param string
   */
  public function setUnit($unit)
  {
    $this->unit = $unit;
  }
  /**
   * @return string
   */
  public function getUnit()
  {
    return $this->unit;
  }
  /**
   * @param string
   */
  public function setValueType($valueType)
  {
    $this->valueType = $valueType;
  }
  /**
   * @return string
   */
  public function getValueType()
  {
    return $this->valueType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleMonitoringV3TimeSeries::class, 'Google_Service_DiscoveryEngine_GoogleMonitoringV3TimeSeries');
