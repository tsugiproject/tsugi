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

namespace Google\Service\CloudCommercePartnerProcurementService;

class ApproveAccountRequest extends \Google\Model
{
  /**
   * @var string
   */
  public $approvalName;
  /**
   * @var string[]
   */
  public $properties;
  /**
   * @var string
   */
  public $reason;

  /**
   * @param string
   */
  public function setApprovalName($approvalName)
  {
    $this->approvalName = $approvalName;
  }
  /**
   * @return string
   */
  public function getApprovalName()
  {
    return $this->approvalName;
  }
  /**
   * @param string[]
   */
  public function setProperties($properties)
  {
    $this->properties = $properties;
  }
  /**
   * @return string[]
   */
  public function getProperties()
  {
    return $this->properties;
  }
  /**
   * @param string
   */
  public function setReason($reason)
  {
    $this->reason = $reason;
  }
  /**
   * @return string
   */
  public function getReason()
  {
    return $this->reason;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ApproveAccountRequest::class, 'Google_Service_CloudCommercePartnerProcurementService_ApproveAccountRequest');
