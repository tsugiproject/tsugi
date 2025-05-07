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

class GoogleCloudDiscoveryengineV1CompletionSuggestion extends \Google\Collection
{
  protected $collection_key = 'alternativePhrases';
  /**
   * @var string[]
   */
  public $alternativePhrases;
  /**
   * @var string
   */
  public $frequency;
  public $globalScore;
  /**
   * @var string
   */
  public $groupId;
  public $groupScore;
  /**
   * @var string
   */
  public $languageCode;
  /**
   * @var string
   */
  public $suggestion;

  /**
   * @param string[]
   */
  public function setAlternativePhrases($alternativePhrases)
  {
    $this->alternativePhrases = $alternativePhrases;
  }
  /**
   * @return string[]
   */
  public function getAlternativePhrases()
  {
    return $this->alternativePhrases;
  }
  /**
   * @param string
   */
  public function setFrequency($frequency)
  {
    $this->frequency = $frequency;
  }
  /**
   * @return string
   */
  public function getFrequency()
  {
    return $this->frequency;
  }
  public function setGlobalScore($globalScore)
  {
    $this->globalScore = $globalScore;
  }
  public function getGlobalScore()
  {
    return $this->globalScore;
  }
  /**
   * @param string
   */
  public function setGroupId($groupId)
  {
    $this->groupId = $groupId;
  }
  /**
   * @return string
   */
  public function getGroupId()
  {
    return $this->groupId;
  }
  public function setGroupScore($groupScore)
  {
    $this->groupScore = $groupScore;
  }
  public function getGroupScore()
  {
    return $this->groupScore;
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
  public function setSuggestion($suggestion)
  {
    $this->suggestion = $suggestion;
  }
  /**
   * @return string
   */
  public function getSuggestion()
  {
    return $this->suggestion;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1CompletionSuggestion::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1CompletionSuggestion');
