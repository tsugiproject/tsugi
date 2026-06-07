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

class GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountRiskReason extends \Google\Model
{
  /**
   * Default unspecified type.
   */
  public const REASON_RISK_REASON_UNSPECIFIED = 'RISK_REASON_UNSPECIFIED';
  /**
   * The client has been observed sending bot-like traffic to this site in the
   * past. This reason incorporates historical reputation and indicates that the
   * client is known to use bots, even if the current request is being made by a
   * human.
   */
  public const REASON_CLIENT_HISTORICAL_BOT_ACTIVITY = 'CLIENT_HISTORICAL_BOT_ACTIVITY';
  /**
   * The account is part of a large group of related accounts, indicating that
   * it may be part of a fraudulent network. Related accounts are identified
   * based on having similar traffic patterns and request characteristics.
   */
  public const REASON_ACCOUNT_IN_LARGE_RELATED_GROUP = 'ACCOUNT_IN_LARGE_RELATED_GROUP';
  /**
   * The client has been observed accessing many accounts on this site.
   */
  public const REASON_CLIENT_ACCESSED_MANY_ACCOUNTS = 'CLIENT_ACCESSED_MANY_ACCOUNTS';
  /**
   * This email domain is a suspected provider of disposable email addresses.
   */
  public const REASON_DISPOSABLE_EMAIL_DOMAIN = 'DISPOSABLE_EMAIL_DOMAIN';
  /**
   * Output only. A risk reason associated with this request.
   *
   * @var string
   */
  public $reason;

  /**
   * Output only. A risk reason associated with this request.
   *
   * Accepted values: RISK_REASON_UNSPECIFIED, CLIENT_HISTORICAL_BOT_ACTIVITY,
   * ACCOUNT_IN_LARGE_RELATED_GROUP, CLIENT_ACCESSED_MANY_ACCOUNTS,
   * DISPOSABLE_EMAIL_DOMAIN
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
class_alias(GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountRiskReason::class, 'Google_Service_RecaptchaEnterprise_GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountRiskReason');
