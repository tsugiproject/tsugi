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

class SeverityCountThreshold extends \Google\Model
{
  /**
   * Default value. This value is unused.
   */
  public const SEVERITY_SEVERITY_UNSPECIFIED = 'SEVERITY_UNSPECIFIED';
  /**
   * Critical severity.
   */
  public const SEVERITY_CRITICAL = 'CRITICAL';
  /**
   * High severity.
   */
  public const SEVERITY_HIGH = 'HIGH';
  /**
   * Medium severity.
   */
  public const SEVERITY_MEDIUM = 'MEDIUM';
  /**
   * Low severity.
   */
  public const SEVERITY_LOW = 'LOW';
  /**
   * Optional. The severity level, reusing the existing Violation.Severity.
   *
   * @var string
   */
  public $severity;
  /**
   * Optional. If violation count meets or exceeds this threshold, validation
   * fails.
   *
   * @var int
   */
  public $thresholdCount;

  /**
   * Optional. The severity level, reusing the existing Violation.Severity.
   *
   * Accepted values: SEVERITY_UNSPECIFIED, CRITICAL, HIGH, MEDIUM, LOW
   *
   * @param self::SEVERITY_* $severity
   */
  public function setSeverity($severity)
  {
    $this->severity = $severity;
  }
  /**
   * @return self::SEVERITY_*
   */
  public function getSeverity()
  {
    return $this->severity;
  }
  /**
   * Optional. If violation count meets or exceeds this threshold, validation
   * fails.
   *
   * @param int $thresholdCount
   */
  public function setThresholdCount($thresholdCount)
  {
    $this->thresholdCount = $thresholdCount;
  }
  /**
   * @return int
   */
  public function getThresholdCount()
  {
    return $this->thresholdCount;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SeverityCountThreshold::class, 'Google_Service_SecurityPosture_SeverityCountThreshold');
