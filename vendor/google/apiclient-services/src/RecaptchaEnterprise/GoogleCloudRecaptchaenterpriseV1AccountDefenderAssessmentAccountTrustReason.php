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

namespace Google\Service\RecaptchaEnterprise;

class GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTrustReason extends \Google\Model
{
  /**
   * Default unspecified type.
   */
  public const REASON_TRUST_REASON_UNSPECIFIED = 'TRUST_REASON_UNSPECIFIED';
  /**
   * The request matches a trusted profile associated with this account.
   * Equivalent to `AccountDefenderLabel.PROFILE_MATCH`.
   */
  public const REASON_PROFILE_MATCH = 'PROFILE_MATCH';
  /**
   * The account's historical activity is reputable. It is unlikely that the
   * account has been compromised in the past.
   */
  public const REASON_ACCOUNT_HISTORY_REPUTABLE = 'ACCOUNT_HISTORY_REPUTABLE';
  /**
   * Output only. A trust reason associated with this request.
   *
   * @var string
   */
  public $reason;

  /**
   * Output only. A trust reason associated with this request.
   *
   * Accepted values: TRUST_REASON_UNSPECIFIED, PROFILE_MATCH,
   * ACCOUNT_HISTORY_REPUTABLE
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTrustReason::class, 'Google_Service_RecaptchaEnterprise_GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTrustReason');
