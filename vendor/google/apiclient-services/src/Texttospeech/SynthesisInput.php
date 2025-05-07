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

class SynthesisInput extends \Google\Model
{
  protected $customPronunciationsType = CustomPronunciations::class;
  protected $customPronunciationsDataType = '';
  protected $multiSpeakerMarkupType = MultiSpeakerMarkup::class;
  protected $multiSpeakerMarkupDataType = '';
  /**
   * @var string
   */
  public $ssml;
  /**
   * @var string
   */
  public $text;

  /**
   * @param CustomPronunciations
   */
  public function setCustomPronunciations(CustomPronunciations $customPronunciations)
  {
    $this->customPronunciations = $customPronunciations;
  }
  /**
   * @return CustomPronunciations
   */
  public function getCustomPronunciations()
  {
    return $this->customPronunciations;
  }
  /**
   * @param MultiSpeakerMarkup
   */
  public function setMultiSpeakerMarkup(MultiSpeakerMarkup $multiSpeakerMarkup)
  {
    $this->multiSpeakerMarkup = $multiSpeakerMarkup;
  }
  /**
   * @return MultiSpeakerMarkup
   */
  public function getMultiSpeakerMarkup()
  {
    return $this->multiSpeakerMarkup;
  }
  /**
   * @param string
   */
  public function setSsml($ssml)
  {
    $this->ssml = $ssml;
  }
  /**
   * @return string
   */
  public function getSsml()
  {
    return $this->ssml;
  }
  /**
   * @param string
   */
  public function setText($text)
  {
    $this->text = $text;
  }
  /**
   * @return string
   */
  public function getText()
  {
    return $this->text;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SynthesisInput::class, 'Google_Service_Texttospeech_SynthesisInput');
