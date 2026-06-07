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

class ComplianceRowData extends \Google\Model
{
  /**
   * Unspecified.
   */
  public const REQUIREMENT_COMPLIANCE_REQUIREMENT_UNSPECIFIED = 'COMPLIANCE_REQUIREMENT_UNSPECIFIED';
  /**
   * Whether the sender has properly configured SPF.
   */
  public const REQUIREMENT_SPF = 'SPF';
  /**
   * Whether the sender has properly configured DKIM.
   */
  public const REQUIREMENT_DKIM = 'DKIM';
  /**
   * Whether the sender has properly configured both SPF and DKIM.
   */
  public const REQUIREMENT_SPF_AND_DKIM = 'SPF_AND_DKIM';
  /**
   * Whether the sender has configured DMARC policy.
   */
  public const REQUIREMENT_DMARC_POLICY = 'DMARC_POLICY';
  /**
   * Whether the From: header is aligned with DKIM or SPF
   */
  public const REQUIREMENT_DMARC_ALIGNMENT = 'DMARC_ALIGNMENT';
  /**
   * Whether messages are correctly formatted according to RFC 5322.
   */
  public const REQUIREMENT_MESSAGE_FORMATTING = 'MESSAGE_FORMATTING';
  /**
   * Whether the domain has forward and reverse DNS records.
   */
  public const REQUIREMENT_DNS_RECORDS = 'DNS_RECORDS';
  /**
   * Whether messages has TLS encryption.
   */
  public const REQUIREMENT_ENCRYPTION = 'ENCRYPTION';
  /**
   * Whether the sender is below a threshold for user-reported spam rate.
   */
  public const REQUIREMENT_USER_REPORTED_SPAM_RATE = 'USER_REPORTED_SPAM_RATE';
  /**
   * Whether the sender sufficiently supports one-click unsubscribe. Note that
   * the user-facing requirement is "one-click unsubscribe", but we require
   * satisfaction of multiple "unsubscribe support" rules.
   */
  public const REQUIREMENT_ONE_CLICK_UNSUBSCRIBE = 'ONE_CLICK_UNSUBSCRIBE';
  /**
   * Whether the sender honors user-initiated unsubscribe requests.
   */
  public const REQUIREMENT_HONOR_UNSUBSCRIBE = 'HONOR_UNSUBSCRIBE';
  /**
   * The compliance requirement.
   *
   * @var string
   */
  public $requirement;
  protected $statusType = ComplianceStatus::class;
  protected $statusDataType = '';

  /**
   * The compliance requirement.
   *
   * Accepted values: COMPLIANCE_REQUIREMENT_UNSPECIFIED, SPF, DKIM,
   * SPF_AND_DKIM, DMARC_POLICY, DMARC_ALIGNMENT, MESSAGE_FORMATTING,
   * DNS_RECORDS, ENCRYPTION, USER_REPORTED_SPAM_RATE, ONE_CLICK_UNSUBSCRIBE,
   * HONOR_UNSUBSCRIBE
   *
   * @param self::REQUIREMENT_* $requirement
   */
  public function setRequirement($requirement)
  {
    $this->requirement = $requirement;
  }
  /**
   * @return self::REQUIREMENT_*
   */
  public function getRequirement()
  {
    return $this->requirement;
  }
  /**
   * The compliance status for the requirement.
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
class_alias(ComplianceRowData::class, 'Google_Service_PostmasterTools_ComplianceRowData');
