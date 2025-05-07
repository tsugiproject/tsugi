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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1CorroborateContentResponse extends \Google\Collection
{
  protected $collection_key = 'claims';
  protected $claimsType = GoogleCloudAiplatformV1Claim::class;
  protected $claimsDataType = 'array';
  /**
   * @var float
   */
  public $corroborationScore;

  /**
   * @param GoogleCloudAiplatformV1Claim[]
   */
  public function setClaims($claims)
  {
    $this->claims = $claims;
  }
  /**
   * @return GoogleCloudAiplatformV1Claim[]
   */
  public function getClaims()
  {
    return $this->claims;
  }
  /**
   * @param float
   */
  public function setCorroborationScore($corroborationScore)
  {
    $this->corroborationScore = $corroborationScore;
  }
  /**
   * @return float
   */
  public function getCorroborationScore()
  {
    return $this->corroborationScore;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1CorroborateContentResponse::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1CorroborateContentResponse');
