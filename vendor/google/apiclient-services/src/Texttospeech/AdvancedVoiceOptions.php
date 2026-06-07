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

class AdvancedVoiceOptions extends \Google\Model
{
  /**
   * Optional. If true, textnorm will be applied to text input. This feature is
   * enabled by default. Only applies for Gemini TTS.
   *
   * @var bool
   */
  public $enableTextnorm;
  /**
   * Only for Journey voices. If false, the synthesis is context aware and has a
   * higher latency.
   *
   * @var bool
   */
  public $lowLatencyJourneySynthesis;
  /**
   * Optional. Input only. Deprecated, use safety_settings instead. If true,
   * relaxes safety filters for Gemini TTS.
   *
   * @deprecated
   * @var bool
   */
  public $relaxSafetyFilters;
  protected $safetySettingsType = SafetySettings::class;
  protected $safetySettingsDataType = '';

  /**
   * Optional. If true, textnorm will be applied to text input. This feature is
   * enabled by default. Only applies for Gemini TTS.
   *
   * @param bool $enableTextnorm
   */
  public function setEnableTextnorm($enableTextnorm)
  {
    $this->enableTextnorm = $enableTextnorm;
  }
  /**
   * @return bool
   */
  public function getEnableTextnorm()
  {
    return $this->enableTextnorm;
  }
  /**
   * Only for Journey voices. If false, the synthesis is context aware and has a
   * higher latency.
   *
   * @param bool $lowLatencyJourneySynthesis
   */
  public function setLowLatencyJourneySynthesis($lowLatencyJourneySynthesis)
  {
    $this->lowLatencyJourneySynthesis = $lowLatencyJourneySynthesis;
  }
  /**
   * @return bool
   */
  public function getLowLatencyJourneySynthesis()
  {
    return $this->lowLatencyJourneySynthesis;
  }
  /**
   * Optional. Input only. Deprecated, use safety_settings instead. If true,
   * relaxes safety filters for Gemini TTS.
   *
   * @deprecated
   * @param bool $relaxSafetyFilters
   */
  public function setRelaxSafetyFilters($relaxSafetyFilters)
  {
    $this->relaxSafetyFilters = $relaxSafetyFilters;
  }
  /**
   * @deprecated
   * @return bool
   */
  public function getRelaxSafetyFilters()
  {
    return $this->relaxSafetyFilters;
  }
  /**
   * Optional. Input only. This applies to Gemini TTS only. If set, the category
   * specified in the safety setting will be blocked if the harm probability is
   * above the threshold. Otherwise, the safety filter will be disabled by
   * default.
   *
   * @param SafetySettings $safetySettings
   */
  public function setSafetySettings(SafetySettings $safetySettings)
  {
    $this->safetySettings = $safetySettings;
  }
  /**
   * @return SafetySettings
   */
  public function getSafetySettings()
  {
    return $this->safetySettings;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AdvancedVoiceOptions::class, 'Google_Service_Texttospeech_AdvancedVoiceOptions');
