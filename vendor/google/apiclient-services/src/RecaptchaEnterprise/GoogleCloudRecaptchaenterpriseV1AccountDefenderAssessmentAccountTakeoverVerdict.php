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

class GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTakeoverVerdict extends \Google\Collection
{
  protected $collection_key = 'trustReasons';
  /**
   * Output only. Account takeover attempt probability. Values are from 0.0
   * (lowest risk) to 1.0 (highest risk).
   *
   * @var float
   */
  public $risk;
  protected $riskReasonsType = GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountRiskReason::class;
  protected $riskReasonsDataType = 'array';
  protected $trustReasonsType = GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTrustReason::class;
  protected $trustReasonsDataType = 'array';

  /**
   * Output only. Account takeover attempt probability. Values are from 0.0
   * (lowest risk) to 1.0 (highest risk).
   *
   * @param float $risk
   */
  public function setRisk($risk)
  {
    $this->risk = $risk;
  }
  /**
   * @return float
   */
  public function getRisk()
  {
    return $this->risk;
  }
  /**
   * Output only. Unordered list. Reasons why the request appears risky. Risk
   * reasons can be returned even if the risk is low, as trustworthy requests
   * can still have some risk signals.
   *
   * @param GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountRiskReason[] $riskReasons
   */
  public function setRiskReasons($riskReasons)
  {
    $this->riskReasons = $riskReasons;
  }
  /**
   * @return GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountRiskReason[]
   */
  public function getRiskReasons()
  {
    return $this->riskReasons;
  }
  /**
   * Output only. Unordered list. Reasons why the request appears trustworthy.
   * Trust reasons can be returned even if the risk is high, as risky requests
   * can still have some trust signals.
   *
   * @param GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTrustReason[] $trustReasons
   */
  public function setTrustReasons($trustReasons)
  {
    $this->trustReasons = $trustReasons;
  }
  /**
   * @return GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTrustReason[]
   */
  public function getTrustReasons()
  {
    return $this->trustReasons;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTakeoverVerdict::class, 'Google_Service_RecaptchaEnterprise_GoogleCloudRecaptchaenterpriseV1AccountDefenderAssessmentAccountTakeoverVerdict');
