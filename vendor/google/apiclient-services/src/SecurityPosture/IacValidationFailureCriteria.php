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

namespace Google\Service\SecurityPosture;

class IacValidationFailureCriteria extends \Google\Collection
{
  protected $collection_key = 'severityCountThresholds';
  /**
   * Output only. The time at which the resource was created.
   *
   * @var string
   */
  public $createTime;
  /**
   * Optional. The etag for optimistic concurrency.
   *
   * @var string
   */
  public $etag;
  /**
   * Identifier. The resource name of the IacValidationFailureCriteria. Format:
   * organizations/{organization}/locations/{location}/iacValidationFailureCrite
   * ria
   *
   * @var string
   */
  public $name;
  protected $severityCountThresholdsType = SeverityCountThreshold::class;
  protected $severityCountThresholdsDataType = 'array';
  /**
   * Output only. The time at which the resource was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Output only. The time at which the resource was created.
   *
   * @param string $createTime
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * Optional. The etag for optimistic concurrency.
   *
   * @param string $etag
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * Identifier. The resource name of the IacValidationFailureCriteria. Format:
   * organizations/{organization}/locations/{location}/iacValidationFailureCrite
   * ria
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
   * Optional. A list of severity thresholds. An IaC validation fails if any
   * threshold is exceeded.
   *
   * @param SeverityCountThreshold[] $severityCountThresholds
   */
  public function setSeverityCountThresholds($severityCountThresholds)
  {
    $this->severityCountThresholds = $severityCountThresholds;
  }
  /**
   * @return SeverityCountThreshold[]
   */
  public function getSeverityCountThresholds()
  {
    return $this->severityCountThresholds;
  }
  /**
   * Output only. The time at which the resource was last updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(IacValidationFailureCriteria::class, 'Google_Service_SecurityPosture_IacValidationFailureCriteria');
