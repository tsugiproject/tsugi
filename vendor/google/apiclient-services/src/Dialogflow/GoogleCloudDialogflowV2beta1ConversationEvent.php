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

class GoogleCloudDialogflowV2beta1ConversationEvent extends \Google\Model
{
  /**
   * @var string
   */
  public $conversation;
  protected $errorStatusType = GoogleRpcStatus::class;
  protected $errorStatusDataType = '';
  protected $newMessagePayloadType = GoogleCloudDialogflowV2beta1Message::class;
  protected $newMessagePayloadDataType = '';
  protected $newRecognitionResultPayloadType = GoogleCloudDialogflowV2beta1StreamingRecognitionResult::class;
  protected $newRecognitionResultPayloadDataType = '';
  /**
   * @var string
   */
  public $type;

  /**
   * @param string
   */
  public function setConversation($conversation)
  {
    $this->conversation = $conversation;
  }
  /**
   * @return string
   */
  public function getConversation()
  {
    return $this->conversation;
  }
  /**
   * @param GoogleRpcStatus
   */
  public function setErrorStatus(GoogleRpcStatus $errorStatus)
  {
    $this->errorStatus = $errorStatus;
  }
  /**
   * @return GoogleRpcStatus
   */
  public function getErrorStatus()
  {
    return $this->errorStatus;
  }
  /**
   * @param GoogleCloudDialogflowV2beta1Message
   */
  public function setNewMessagePayload(GoogleCloudDialogflowV2beta1Message $newMessagePayload)
  {
    $this->newMessagePayload = $newMessagePayload;
  }
  /**
   * @return GoogleCloudDialogflowV2beta1Message
   */
  public function getNewMessagePayload()
  {
    return $this->newMessagePayload;
  }
  /**
   * @param GoogleCloudDialogflowV2beta1StreamingRecognitionResult
   */
  public function setNewRecognitionResultPayload(GoogleCloudDialogflowV2beta1StreamingRecognitionResult $newRecognitionResultPayload)
  {
    $this->newRecognitionResultPayload = $newRecognitionResultPayload;
  }
  /**
   * @return GoogleCloudDialogflowV2beta1StreamingRecognitionResult
   */
  public function getNewRecognitionResultPayload()
  {
    return $this->newRecognitionResultPayload;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDialogflowV2beta1ConversationEvent::class, 'Google_Service_Dialogflow_GoogleCloudDialogflowV2beta1ConversationEvent');
