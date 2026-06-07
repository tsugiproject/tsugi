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

namespace Google\Service\AlertCenter;

class ActionInfo extends \Google\Model
{
  /**
   * Google Cloud Storage location of the content that violated the rule. This
   * field has format: "/"
   *
   * @var string
   */
  public $evidenceLockerFilePath;

  /**
   * Google Cloud Storage location of the content that violated the rule. This
   * field has format: "/"
   *
   * @param string $evidenceLockerFilePath
   */
  public function setEvidenceLockerFilePath($evidenceLockerFilePath)
  {
    $this->evidenceLockerFilePath = $evidenceLockerFilePath;
  }
  /**
   * @return string
   */
  public function getEvidenceLockerFilePath()
  {
    return $this->evidenceLockerFilePath;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ActionInfo::class, 'Google_Service_AlertCenter_ActionInfo');
