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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1betaLanguageInfo extends \Google\Model
{
  /**
   * @var string
   */
  public $language;
  /**
   * @var string
   */
  public $languageCode;
  /**
   * @var string
   */
  public $normalizedLanguageCode;
  /**
   * @var string
   */
  public $region;

  /**
   * @param string
   */
  public function setLanguage($language)
  {
    $this->language = $language;
  }
  /**
   * @return string
   */
  public function getLanguage()
  {
    return $this->language;
  }
  /**
   * @param string
   */
  public function setLanguageCode($languageCode)
  {
    $this->languageCode = $languageCode;
  }
  /**
   * @return string
   */
  public function getLanguageCode()
  {
    return $this->languageCode;
  }
  /**
   * @param string
   */
  public function setNormalizedLanguageCode($normalizedLanguageCode)
  {
    $this->normalizedLanguageCode = $normalizedLanguageCode;
  }
  /**
   * @return string
   */
  public function getNormalizedLanguageCode()
  {
    return $this->normalizedLanguageCode;
  }
  /**
   * @param string
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1betaLanguageInfo::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1betaLanguageInfo');
