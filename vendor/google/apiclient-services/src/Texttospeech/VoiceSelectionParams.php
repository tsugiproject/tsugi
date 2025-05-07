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

class VoiceSelectionParams extends \Google\Model
{
  protected $customVoiceType = CustomVoiceParams::class;
  protected $customVoiceDataType = '';
  /**
   * @var string
   */
  public $languageCode;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $ssmlGender;
  protected $voiceCloneType = VoiceCloneParams::class;
  protected $voiceCloneDataType = '';

  /**
   * @param CustomVoiceParams
   */
  public function setCustomVoice(CustomVoiceParams $customVoice)
  {
    $this->customVoice = $customVoice;
  }
  /**
   * @return CustomVoiceParams
   */
  public function getCustomVoice()
  {
    return $this->customVoice;
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
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param string
   */
  public function setSsmlGender($ssmlGender)
  {
    $this->ssmlGender = $ssmlGender;
  }
  /**
   * @return string
   */
  public function getSsmlGender()
  {
    return $this->ssmlGender;
  }
  /**
   * @param VoiceCloneParams
   */
  public function setVoiceClone(VoiceCloneParams $voiceClone)
  {
    $this->voiceClone = $voiceClone;
  }
  /**
   * @return VoiceCloneParams
   */
  public function getVoiceClone()
  {
    return $this->voiceClone;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(VoiceSelectionParams::class, 'Google_Service_Texttospeech_VoiceSelectionParams');
