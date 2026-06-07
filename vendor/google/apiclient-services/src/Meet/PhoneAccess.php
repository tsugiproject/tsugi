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

namespace Google\Service\Meet;

class PhoneAccess extends \Google\Model
{
  /**
   * The BCP 47/LDML language code for the language associated with this phone
   * access. To be parsed by the i18n LanguageCode utility. Examples: "es-419"
   * for Latin American Spanish, "fr-CA" for Canadian French.
   *
   * @var string
   */
  public $languageCode;
  /**
   * The phone number to dial for this meeting space in E.164 format. Full phone
   * number with a leading '+' character.
   *
   * @var string
   */
  public $phoneNumber;
  /**
   * The PIN that users must enter after dialing the given number. The PIN
   * consists of only decimal digits and the length may vary.
   *
   * @var string
   */
  public $pin;
  /**
   * The CLDR/ISO 3166 region code for the country associated with this phone
   * access. To be parsed by the i18n RegionCode utility. Example: "SE" for
   * Sweden.
   *
   * @var string
   */
  public $regionCode;

  /**
   * The BCP 47/LDML language code for the language associated with this phone
   * access. To be parsed by the i18n LanguageCode utility. Examples: "es-419"
   * for Latin American Spanish, "fr-CA" for Canadian French.
   *
   * @param string $languageCode
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
   * The phone number to dial for this meeting space in E.164 format. Full phone
   * number with a leading '+' character.
   *
   * @param string $phoneNumber
   */
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;
  }
  /**
   * @return string
   */
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }
  /**
   * The PIN that users must enter after dialing the given number. The PIN
   * consists of only decimal digits and the length may vary.
   *
   * @param string $pin
   */
  public function setPin($pin)
  {
    $this->pin = $pin;
  }
  /**
   * @return string
   */
  public function getPin()
  {
    return $this->pin;
  }
  /**
   * The CLDR/ISO 3166 region code for the country associated with this phone
   * access. To be parsed by the i18n RegionCode utility. Example: "SE" for
   * Sweden.
   *
   * @param string $regionCode
   */
  public function setRegionCode($regionCode)
  {
    $this->regionCode = $regionCode;
  }
  /**
   * @return string
   */
  public function getRegionCode()
  {
    return $this->regionCode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PhoneAccess::class, 'Google_Service_Meet_PhoneAccess');
