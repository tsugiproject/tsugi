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

namespace Google\Service\CustomerEngagementSuite;

class LfA2aV1SendMessageRequest extends \Google\Model
{
  protected $configurationType = LfA2aV1SendMessageConfiguration::class;
  protected $configurationDataType = '';
  protected $messageType = LfA2aV1Message::class;
  protected $messageDataType = '';
  /**
   * A flexible key-value map for passing additional context or parameters.
   *
   * @var array[]
   */
  public $metadata;

  /**
   * Configuration for the send request.
   *
   * @param LfA2aV1SendMessageConfiguration $configuration
   */
  public function setConfiguration(LfA2aV1SendMessageConfiguration $configuration)
  {
    $this->configuration = $configuration;
  }
  /**
   * @return LfA2aV1SendMessageConfiguration
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }
  /**
   * Required. The message to send to the agent.
   *
   * @param LfA2aV1Message $message
   */
  public function setMessage(LfA2aV1Message $message)
  {
    $this->message = $message;
  }
  /**
   * @return LfA2aV1Message
   */
  public function getMessage()
  {
    return $this->message;
  }
  /**
   * A flexible key-value map for passing additional context or parameters.
   *
   * @param array[] $metadata
   */
  public function setMetadata($metadata)
  {
    $this->metadata = $metadata;
  }
  /**
   * @return array[]
   */
  public function getMetadata()
  {
    return $this->metadata;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LfA2aV1SendMessageRequest::class, 'Google_Service_CustomerEngagementSuite_LfA2aV1SendMessageRequest');
