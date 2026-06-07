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

class GooglePrivacyDlpV2MetadataKeyValueExpression extends \Google\Model
{
  /**
   * The regular expression for the key. Key should be non-empty.
   *
   * @var string
   */
  public $keyRegex;
  /**
   * The regular expression for the value. Value should be non-empty.
   *
   * @var string
   */
  public $valueRegex;

  /**
   * The regular expression for the key. Key should be non-empty.
   *
   * @param string $keyRegex
   */
  public function setKeyRegex($keyRegex)
  {
    $this->keyRegex = $keyRegex;
  }
  /**
   * @return string
   */
  public function getKeyRegex()
  {
    return $this->keyRegex;
  }
  /**
   * The regular expression for the value. Value should be non-empty.
   *
   * @param string $valueRegex
   */
  public function setValueRegex($valueRegex)
  {
    $this->valueRegex = $valueRegex;
  }
  /**
   * @return string
   */
  public function getValueRegex()
  {
    return $this->valueRegex;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2MetadataKeyValueExpression::class, 'Google_Service_DLP_GooglePrivacyDlpV2MetadataKeyValueExpression');
