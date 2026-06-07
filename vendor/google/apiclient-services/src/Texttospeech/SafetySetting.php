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

namespace Google\Service\Texttospeech;

class SafetySetting extends \Google\Model
{
  /**
   * Default value. This value is unused.
   */
  public const CATEGORY_HARM_CATEGORY_UNSPECIFIED = 'HARM_CATEGORY_UNSPECIFIED';
  /**
   * Content that promotes violence or incites hatred against individuals or
   * groups based on certain attributes.
   */
  public const CATEGORY_HARM_CATEGORY_HATE_SPEECH = 'HARM_CATEGORY_HATE_SPEECH';
  /**
   * Content that promotes, facilitates, or enables dangerous activities.
   */
  public const CATEGORY_HARM_CATEGORY_DANGEROUS_CONTENT = 'HARM_CATEGORY_DANGEROUS_CONTENT';
  /**
   * Abusive, threatening, or content intended to bully, torment, or ridicule.
   */
  public const CATEGORY_HARM_CATEGORY_HARASSMENT = 'HARM_CATEGORY_HARASSMENT';
  /**
   * Content that contains sexually explicit material.
   */
  public const CATEGORY_HARM_CATEGORY_SEXUALLY_EXPLICIT = 'HARM_CATEGORY_SEXUALLY_EXPLICIT';
  /**
   * The harm block threshold is unspecified.
   */
  public const THRESHOLD_HARM_BLOCK_THRESHOLD_UNSPECIFIED = 'HARM_BLOCK_THRESHOLD_UNSPECIFIED';
  /**
   * Block content with a low harm probability or higher.
   */
  public const THRESHOLD_BLOCK_LOW_AND_ABOVE = 'BLOCK_LOW_AND_ABOVE';
  /**
   * Block content with a medium harm probability or higher.
   */
  public const THRESHOLD_BLOCK_MEDIUM_AND_ABOVE = 'BLOCK_MEDIUM_AND_ABOVE';
  /**
   * Block content with a high harm probability.
   */
  public const THRESHOLD_BLOCK_ONLY_HIGH = 'BLOCK_ONLY_HIGH';
  /**
   * Do not block any content, regardless of its harm probability.
   */
  public const THRESHOLD_BLOCK_NONE = 'BLOCK_NONE';
  /**
   * Turn off the safety filter entirely.
   */
  public const THRESHOLD_OFF = 'OFF';
  /**
   * The harm category to apply the safety setting to.
   *
   * @var string
   */
  public $category;
  /**
   * The harm block threshold for the safety setting.
   *
   * @var string
   */
  public $threshold;

  /**
   * The harm category to apply the safety setting to.
   *
   * Accepted values: HARM_CATEGORY_UNSPECIFIED, HARM_CATEGORY_HATE_SPEECH,
   * HARM_CATEGORY_DANGEROUS_CONTENT, HARM_CATEGORY_HARASSMENT,
   * HARM_CATEGORY_SEXUALLY_EXPLICIT
   *
   * @param self::CATEGORY_* $category
   */
  public function setCategory($category)
  {
    $this->category = $category;
  }
  /**
   * @return self::CATEGORY_*
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * The harm block threshold for the safety setting.
   *
   * Accepted values: HARM_BLOCK_THRESHOLD_UNSPECIFIED, BLOCK_LOW_AND_ABOVE,
   * BLOCK_MEDIUM_AND_ABOVE, BLOCK_ONLY_HIGH, BLOCK_NONE, OFF
   *
   * @param self::THRESHOLD_* $threshold
   */
  public function setThreshold($threshold)
  {
    $this->threshold = $threshold;
  }
  /**
   * @return self::THRESHOLD_*
   */
  public function getThreshold()
  {
    return $this->threshold;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SafetySetting::class, 'Google_Service_Texttospeech_SafetySetting');
