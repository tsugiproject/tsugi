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

namespace Google\Service\AndroidManagement;

class CommonCriteriaModeInfo extends \Google\Model
{
  /**
   * @var string
   */
  public $commonCriteriaModeStatus;
  /**
   * @var string
   */
  public $policySignatureVerificationStatus;

  /**
   * @param string
   */
  public function setCommonCriteriaModeStatus($commonCriteriaModeStatus)
  {
    $this->commonCriteriaModeStatus = $commonCriteriaModeStatus;
  }
  /**
   * @return string
   */
  public function getCommonCriteriaModeStatus()
  {
    return $this->commonCriteriaModeStatus;
  }
  /**
   * @param string
   */
  public function setPolicySignatureVerificationStatus($policySignatureVerificationStatus)
  {
    $this->policySignatureVerificationStatus = $policySignatureVerificationStatus;
  }
  /**
   * @return string
   */
  public function getPolicySignatureVerificationStatus()
  {
    return $this->policySignatureVerificationStatus;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CommonCriteriaModeInfo::class, 'Google_Service_AndroidManagement_CommonCriteriaModeInfo');
