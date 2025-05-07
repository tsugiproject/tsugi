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

namespace Google\Service\CloudDeploy;

class PolicyViolation extends \Google\Collection
{
  protected $collection_key = 'policyViolationDetails';
  protected $policyViolationDetailsType = PolicyViolationDetails::class;
  protected $policyViolationDetailsDataType = 'array';

  /**
   * @param PolicyViolationDetails[]
   */
  public function setPolicyViolationDetails($policyViolationDetails)
  {
    $this->policyViolationDetails = $policyViolationDetails;
  }
  /**
   * @return PolicyViolationDetails[]
   */
  public function getPolicyViolationDetails()
  {
    return $this->policyViolationDetails;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PolicyViolation::class, 'Google_Service_CloudDeploy_PolicyViolation');
