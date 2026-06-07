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

class HonorUnsubscribeVerdict extends \Google\Model
{
  /**
   * Unspecified.
   */
  public const REASON_REASON_UNSPECIFIED = 'REASON_UNSPECIFIED';
  /**
   * The sender does not honor unsubscribe requests.
   */
  public const REASON_NOT_HONORING = 'NOT_HONORING';
  /**
   * The sender does not honor unsubscribe requests and consider to increase the
   * number of relevant campaigns.
   */
  public const REASON_NOT_HONORING_TOO_FEW_CAMPAIGNS = 'NOT_HONORING_TOO_FEW_CAMPAIGNS';
  /**
   * The sender does not honor unsubscribe requests and consider to reduce the
   * number of relevant campaigns.
   */
  public const REASON_NOT_HONORING_TOO_MANY_CAMPAIGNS = 'NOT_HONORING_TOO_MANY_CAMPAIGNS';
  /**
   * The specific reason for the compliance verdict. Must be empty if the status
   * is compliant.
   *
   * @var string
   */
  public $reason;
  protected $statusType = ComplianceStatus::class;
  protected $statusDataType = '';

  /**
   * The specific reason for the compliance verdict. Must be empty if the status
   * is compliant.
   *
   * Accepted values: REASON_UNSPECIFIED, NOT_HONORING,
   * NOT_HONORING_TOO_FEW_CAMPAIGNS, NOT_HONORING_TOO_MANY_CAMPAIGNS
   *
   * @param self::REASON_* $reason
   */
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  /**
   * @return self::REASON_*
   */
  public function getReason()
  {
    return $this->reason;
  }
  /**
   * The compliance status.
   *
   * @param ComplianceStatus $status
   */
  public function setStatus(ComplianceStatus $status)
  {
    $this->status = $status;
  }
  /**
   * @return ComplianceStatus
   */
  public function getStatus()
  {
    return $this->status;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HonorUnsubscribeVerdict::class, 'Google_Service_PostmasterTools_HonorUnsubscribeVerdict');
