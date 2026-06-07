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

class GooglePrivacyDlpV2KeyValueMetadataLabel extends \Google\Model
{
  /**
   * The metadata key. The format depends on the source of the metadata.
   * Example: - `MSIP_Label_122709e3-8f6b-4860-985f-7f722a94f61e_Enabled` (a
   * Microsoft Purview Information Protection key example)
   *
   * @var string
   */
  public $key;

  /**
   * The metadata key. The format depends on the source of the metadata.
   * Example: - `MSIP_Label_122709e3-8f6b-4860-985f-7f722a94f61e_Enabled` (a
   * Microsoft Purview Information Protection key example)
   *
   * @param string $key
   */
  public function setKey($key)
  {
    $this->key = $key;
  }
  /**
   * @return string
   */
  public function getKey()
  {
    return $this->key;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2KeyValueMetadataLabel::class, 'Google_Service_DLP_GooglePrivacyDlpV2KeyValueMetadataLabel');
