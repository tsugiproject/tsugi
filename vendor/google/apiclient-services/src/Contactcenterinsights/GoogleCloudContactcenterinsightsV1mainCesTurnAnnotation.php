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

namespace Google\Service\Contactcenterinsights;

class GoogleCloudContactcenterinsightsV1mainCesTurnAnnotation extends \Google\Collection
{
  protected $collection_key = 'messages';
  protected $messagesType = GoogleCloudCesV1mainMessage::class;
  protected $messagesDataType = 'array';
  protected $rootSpanType = GoogleCloudCesV1mainSpan::class;
  protected $rootSpanDataType = '';

  /**
   * The messages in the turn.
   *
   * @param GoogleCloudCesV1mainMessage[] $messages
   */
  public function setMessages($messages)
  {
    $this->messages = $messages;
  }
  /**
   * @return GoogleCloudCesV1mainMessage[]
   */
  public function getMessages()
  {
    return $this->messages;
  }
  /**
   * The root span of the action processing.
   *
   * @param GoogleCloudCesV1mainSpan $rootSpan
   */
  public function setRootSpan(GoogleCloudCesV1mainSpan $rootSpan)
  {
    $this->rootSpan = $rootSpan;
  }
  /**
   * @return GoogleCloudCesV1mainSpan
   */
  public function getRootSpan()
  {
    return $this->rootSpan;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1mainCesTurnAnnotation::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1mainCesTurnAnnotation');
