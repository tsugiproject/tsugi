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

namespace Google\Service\Dialogflow;

class GoogleCloudDialogflowV2beta1ResponseMessageTelephonyTransferCall extends \Google\Model
{
  /**
   * @var string
   */
  public $phoneNumber;
  /**
   * @var string
   */
  public $sipUri;

  /**
   * @param string
   */
  public function setPhoneNumber($phoneNumber)
  {
    $this->phoneNumber = $phoneNumber;
  }
  /**
   * @return string
   */
  public function getPhoneNumber()
  {
    return $this->phoneNumber;
  }
  /**
   * @param string
   */
  public function setSipUri($sipUri)
  {
    $this->sipUri = $sipUri;
  }
  /**
   * @return string
   */
  public function getSipUri()
  {
    return $this->sipUri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDialogflowV2beta1ResponseMessageTelephonyTransferCall::class, 'Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1ResponseMessageTelephonyTransferCall');
