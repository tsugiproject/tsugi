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

namespace Google\Service\CloudDataplex;

class GoogleCloudDataplexV1DataProductAccessApprovalConfig extends \Google\Collection
{
  protected $collection_key = 'approverEmails';
  /**
   * Optional. Specifies the email addresses of users who are potential
   * approvers and are notified when an access request is made for the data
   * product. The maximum number of emails allowed is 10.
   *
   * @var string[]
   */
  public $approverEmails;

  /**
   * Optional. Specifies the email addresses of users who are potential
   * approvers and are notified when an access request is made for the data
   * product. The maximum number of emails allowed is 10.
   *
   * @param string[] $approverEmails
   */
  public function setApproverEmails($approverEmails)
  {
    $this->approverEmails = $approverEmails;
  }
  /**
   * @return string[]
   */
  public function getApproverEmails()
  {
    return $this->approverEmails;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataProductAccessApprovalConfig::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataProductAccessApprovalConfig');
