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

class GoogleCloudRecaptchaenterpriseV1RiskAnalysis extends \Google\Collection
{
  protected $collection_key = 'reasons';
  /**
   * @var string
   */
  public $challenge;
  /**
   * @var string[]
   */
  public $extendedVerdictReasons;
  /**
   * @var string[]
   */
  public $reasons;
  /**
   * @var float
   */
  public $score;

  /**
   * @param string
   */
  public function setChallenge($challenge)
  {
    $this->challenge = $challenge;
  }
  /**
   * @return string
   */
  public function getChallenge()
  {
    return $this->challenge;
  }
  /**
   * @param string[]
   */
  public function setExtendedVerdictReasons($extendedVerdictReasons)
  {
    $this->extendedVerdictReasons = $extendedVerdictReasons;
  }
  /**
   * @return string[]
   */
  public function getExtendedVerdictReasons()
  {
    return $this->extendedVerdictReasons;
  }
  /**
   * @param string[]
   */
  public function setReasons($reasons)
  {
    $this->reasons = $reasons;
  }
  /**
   * @return string[]
   */
  public function getReasons()
  {
    return $this->reasons;
  }
  /**
   * @param float
   */
  public function setScore($score)
  {
    $this->score = $score;
  }
  /**
   * @return float
   */
  public function getScore()
  {
    return $this->score;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudRecaptchaenterpriseV1RiskAnalysis::class, 'Google_Service_RecaptchaEnterprise_GoogleCloudRecaptchaenterpriseV1RiskAnalysis');
