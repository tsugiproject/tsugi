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

namespace Google\Service\CloudSearch;

class QuerySuggestion extends \Google\Model
{
  /**
   * Source corpus is unspecified.
   */
  public const SOURCE_CORPUS_SOURCE_CORPUS_UNSPECIFIED = 'SOURCE_CORPUS_UNSPECIFIED';
  /**
   * Source corpus is Gmail.
   */
  public const SOURCE_CORPUS_GMAIL = 'GMAIL';
  /**
   * Source corpus is Drive.
   */
  public const SOURCE_CORPUS_DRIVE = 'DRIVE';
  /**
   * Source corpus is Chat.
   */
  public const SOURCE_CORPUS_CHAT = 'CHAT';
  /**
   * Source corpus is Calendar.
   */
  public const SOURCE_CORPUS_CALENDAR = 'CALENDAR';
  /**
   * Last query time of the suggestion for query history suggestions.
   *
   * @var string
   */
  public $lastQueryTime;
  /**
   * Source corpus of the suggestion.
   *
   * @var string
   */
  public $sourceCorpus;

  /**
   * Last query time of the suggestion for query history suggestions.
   *
   * @param string $lastQueryTime
   */
  public function setLastQueryTime($lastQueryTime)
  {
    $this->lastQueryTime = $lastQueryTime;
  }
  /**
   * @return string
   */
  public function getLastQueryTime()
  {
    return $this->lastQueryTime;
  }
  /**
   * Source corpus of the suggestion.
   *
   * Accepted values: SOURCE_CORPUS_UNSPECIFIED, GMAIL, DRIVE, CHAT, CALENDAR
   *
   * @param self::SOURCE_CORPUS_* $sourceCorpus
   */
  public function setSourceCorpus($sourceCorpus)
  {
    $this->sourceCorpus = $sourceCorpus;
  }
  /**
   * @return self::SOURCE_CORPUS_*
   */
  public function getSourceCorpus()
  {
    return $this->sourceCorpus;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(QuerySuggestion::class, 'Google_Service_CloudSearch_QuerySuggestion');
