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

namespace Google\Service\DLP;

class GooglePrivacyDlpV2TagCondition extends \Google\Model
{
  protected $sensitivityScoreType = GooglePrivacyDlpV2SensitivityScore::class;
  protected $sensitivityScoreDataType = '';
  protected $tagType = GooglePrivacyDlpV2TagValue::class;
  protected $tagDataType = '';

  /**
   * @param GooglePrivacyDlpV2SensitivityScore
   */
  public function setSensitivityScore(GooglePrivacyDlpV2SensitivityScore $sensitivityScore)
  {
    $this->sensitivityScore = $sensitivityScore;
  }
  /**
   * @return GooglePrivacyDlpV2SensitivityScore
   */
  public function getSensitivityScore()
  {
    return $this->sensitivityScore;
  }
  /**
   * @param GooglePrivacyDlpV2TagValue
   */
  public function setTag(GooglePrivacyDlpV2TagValue $tag)
  {
    $this->tag = $tag;
  }
  /**
   * @return GooglePrivacyDlpV2TagValue
   */
  public function getTag()
  {
    return $this->tag;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2TagCondition::class, 'Google_Service_DLP_GooglePrivacyDlpV2TagCondition');
