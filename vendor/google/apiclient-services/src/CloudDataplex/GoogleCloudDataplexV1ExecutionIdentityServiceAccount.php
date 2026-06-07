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

class GoogleCloudDataplexV1ExecutionIdentityServiceAccount extends \Google\Model
{
  /**
   * Required. Service account email. The datascan will execute with this
   * service account's credentials. The user calling this API must have
   * permissions to act as this service account. Dataplex service agent must be
   * granted iam.serviceAccounts.getAccessToken permission on this service
   * account, for example, through the iam.serviceAccountTokenCreator role .
   *
   * @var string
   */
  public $email;

  /**
   * Required. Service account email. The datascan will execute with this
   * service account's credentials. The user calling this API must have
   * permissions to act as this service account. Dataplex service agent must be
   * granted iam.serviceAccounts.getAccessToken permission on this service
   * account, for example, through the iam.serviceAccountTokenCreator role .
   *
   * @param string $email
   */
  public function setEmail($email)
  {
    $this->email = $email;
  }
  /**
   * @return string
   */
  public function getEmail()
  {
    return $this->email;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1ExecutionIdentityServiceAccount::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1ExecutionIdentityServiceAccount');
