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

namespace Google\Service\Compute;

class RolloutPlanWaveOrchestrationOptionsDelay extends \Google\Model
{
  /**
   * The delay will also be added between batches of projects corresponding to
   * the same location.
   */
  public const DELIMITER_DELIMITER_BATCH = 'DELIMITER_BATCH';
  /**
   * The delay will only be added between batches of projects corresponding to
   * different locations.
   */
  public const DELIMITER_DELIMITER_LOCATION = 'DELIMITER_LOCATION';
  /**
   * No delay will be added between batches of projects. Processing will
   * continue with the next batch as soon as the previous batch of LROs is done.
   */
  public const DELIMITER_DELIMITER_UNSPECIFIED = 'DELIMITER_UNSPECIFIED';
  /**
   * The total processing time for each batch of projects will be padded if
   * needed to meet the specified delay duration.
   */
  public const TYPE_TYPE_MINIMUM = 'TYPE_MINIMUM';
  /**
   * The specified delay will directly be added after each batch of projects as
   * specified by the delimiter.
   */
  public const TYPE_TYPE_OFFSET = 'TYPE_OFFSET';
  /**
   * No delay will be added between batches of projects. Processing will
   * continue with the next batch as soon as the previous batch of LROs is done.
   */
  public const TYPE_TYPE_UNSPECIFIED = 'TYPE_UNSPECIFIED';
  /**
   * Optional. Controls whether the delay should only be added between batches
   * of projects corresponding to different locations, or also between batches
   * of projects corresponding to the same location.
   *
   * Must be set to DELIMITER_UNSPECIFIED if no delay is to be added.
   *
   * @var string
   */
  public $delimiter;
  /**
   * Optional. The duration of the delay, if any, to be added between batches of
   * projects. A zero duration corresponds to no delay.
   *
   * @var string
   */
  public $duration;
  /**
   * Optional. Controls whether the specified duration is to be added at the end
   * of each batch, or if the total processing time for each batch will be
   * padded if needed to meet the specified duration.
   *
   * Must be set to TYPE_UNSPECIFIED if no delay is to be added.
   *
   * @var string
   */
  public $type;

  /**
   * Optional. Controls whether the delay should only be added between batches
   * of projects corresponding to different locations, or also between batches
   * of projects corresponding to the same location.
   *
   * Must be set to DELIMITER_UNSPECIFIED if no delay is to be added.
   *
   * Accepted values: DELIMITER_BATCH, DELIMITER_LOCATION, DELIMITER_UNSPECIFIED
   *
   * @param self::DELIMITER_* $delimiter
   */
  public function setDelimiter($delimiter)
  {
    $this->delimiter = $delimiter;
  }
  /**
   * @return self::DELIMITER_*
   */
  public function getDelimiter()
  {
    return $this->delimiter;
  }
  /**
   * Optional. The duration of the delay, if any, to be added between batches of
   * projects. A zero duration corresponds to no delay.
   *
   * @param string $duration
   */
  public function setDuration($duration)
  {
    $this->duration = $duration;
  }
  /**
   * @return string
   */
  public function getDuration()
  {
    return $this->duration;
  }
  /**
   * Optional. Controls whether the specified duration is to be added at the end
   * of each batch, or if the total processing time for each batch will be
   * padded if needed to meet the specified duration.
   *
   * Must be set to TYPE_UNSPECIFIED if no delay is to be added.
   *
   * Accepted values: TYPE_MINIMUM, TYPE_OFFSET, TYPE_UNSPECIFIED
   *
   * @param self::TYPE_* $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return self::TYPE_*
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlanWaveOrchestrationOptionsDelay::class, 'Google_Service_Compute_RolloutPlanWaveOrchestrationOptionsDelay');
