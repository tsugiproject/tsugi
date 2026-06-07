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

class ComplianceStatus extends \Google\Model
{
  /**
   * Unspecified.
   */
  public const STATUS_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * The compliance requirement is met, and the sender is deemed compliant.
   */
  public const STATUS_COMPLIANT = 'COMPLIANT';
  /**
   * The compliance requirement is unmet, and the sender needs to do work to
   * achieve compliance.
   */
  public const STATUS_NEEDS_WORK = 'NEEDS_WORK';
  /**
   * Output only. The compliance status.
   *
   * @var string
   */
  public $status;

  /**
   * Output only. The compliance status.
   *
   * Accepted values: STATE_UNSPECIFIED, COMPLIANT, NEEDS_WORK
   *
   * @param self::STATUS_* $status
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }
  /**
   * @return self::STATUS_*
   */
  public function getStatus()
  {
    return $this->status;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ComplianceStatus::class, 'Google_Service_PostmasterTools_ComplianceStatus');
