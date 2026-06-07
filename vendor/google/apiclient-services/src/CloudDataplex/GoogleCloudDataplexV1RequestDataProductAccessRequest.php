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

class GoogleCloudDataplexV1RequestDataProductAccessRequest extends \Google\Model
{
  protected $changeRequestType = GoogleCloudDataplexV1ChangeRequest::class;
  protected $changeRequestDataType = '';
  /**
   * Optional. Validates the request without actually creating the access change
   * request. Defaults to false.
   *
   * @var bool
   */
  public $validateOnly;

  /**
   * Required. The change request for the data product access request.
   *
   * @param GoogleCloudDataplexV1ChangeRequest $changeRequest
   */
  public function setChangeRequest(GoogleCloudDataplexV1ChangeRequest $changeRequest)
  {
    $this->changeRequest = $changeRequest;
  }
  /**
   * @return GoogleCloudDataplexV1ChangeRequest
   */
  public function getChangeRequest()
  {
    return $this->changeRequest;
  }
  /**
   * Optional. Validates the request without actually creating the access change
   * request. Defaults to false.
   *
   * @param bool $validateOnly
   */
  public function setValidateOnly($validateOnly)
  {
    $this->validateOnly = $validateOnly;
  }
  /**
   * @return bool
   */
  public function getValidateOnly()
  {
    return $this->validateOnly;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1RequestDataProductAccessRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1RequestDataProductAccessRequest');
