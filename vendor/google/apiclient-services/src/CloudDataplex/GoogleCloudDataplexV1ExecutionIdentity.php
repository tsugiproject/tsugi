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

class GoogleCloudDataplexV1ExecutionIdentity extends \Google\Model
{
  protected $dataplexServiceAgentType = GoogleCloudDataplexV1ExecutionIdentityDataplexServiceAgent::class;
  protected $dataplexServiceAgentDataType = '';
  protected $serviceAccountType = GoogleCloudDataplexV1ExecutionIdentityServiceAccount::class;
  protected $serviceAccountDataType = '';
  protected $userCredentialType = GoogleCloudDataplexV1ExecutionIdentityUserCredential::class;
  protected $userCredentialDataType = '';

  /**
   * Optional. The Dataplex service agent associated with the user's project.
   *
   * @param GoogleCloudDataplexV1ExecutionIdentityDataplexServiceAgent $dataplexServiceAgent
   */
  public function setDataplexServiceAgent(GoogleCloudDataplexV1ExecutionIdentityDataplexServiceAgent $dataplexServiceAgent)
  {
    $this->dataplexServiceAgent = $dataplexServiceAgent;
  }
  /**
   * @return GoogleCloudDataplexV1ExecutionIdentityDataplexServiceAgent
   */
  public function getDataplexServiceAgent()
  {
    return $this->dataplexServiceAgent;
  }
  /**
   * Optional. The provided service account.
   *
   * @param GoogleCloudDataplexV1ExecutionIdentityServiceAccount $serviceAccount
   */
  public function setServiceAccount(GoogleCloudDataplexV1ExecutionIdentityServiceAccount $serviceAccount)
  {
    $this->serviceAccount = $serviceAccount;
  }
  /**
   * @return GoogleCloudDataplexV1ExecutionIdentityServiceAccount
   */
  public function getServiceAccount()
  {
    return $this->serviceAccount;
  }
  /**
   * Optional. The credential of the calling user. Supports only ONE_TIME
   * trigger type.
   *
   * @param GoogleCloudDataplexV1ExecutionIdentityUserCredential $userCredential
   */
  public function setUserCredential(GoogleCloudDataplexV1ExecutionIdentityUserCredential $userCredential)
  {
    $this->userCredential = $userCredential;
  }
  /**
   * @return GoogleCloudDataplexV1ExecutionIdentityUserCredential
   */
  public function getUserCredential()
  {
    return $this->userCredential;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1ExecutionIdentity::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1ExecutionIdentity');
