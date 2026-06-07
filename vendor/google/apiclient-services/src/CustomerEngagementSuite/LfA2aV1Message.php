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

class LfA2aV1Message extends \Google\Collection
{
  /**
   * The role is unspecified.
   */
  public const ROLE_ROLE_UNSPECIFIED = 'ROLE_UNSPECIFIED';
  /**
   * The message is from the client to the server.
   */
  public const ROLE_ROLE_USER = 'ROLE_USER';
  /**
   * The message is from the server to the client.
   */
  public const ROLE_ROLE_AGENT = 'ROLE_AGENT';
  protected $collection_key = 'referenceTaskIds';
  /**
   * Optional. The context id of the message. If set, the message will be
   * associated with the given context.
   *
   * @var string
   */
  public $contextId;
  /**
   * The URIs of extensions that are present or contributed to this Message.
   *
   * @var string[]
   */
  public $extensions;
  /**
   * Required. The unique identifier (e.g. UUID) of the message. This is created
   * by the message creator.
   *
   * @var string
   */
  public $messageId;
  /**
   * Optional. Any metadata to provide along with the message.
   *
   * @var array[]
   */
  public $metadata;
  protected $partsType = LfA2aV1Part::class;
  protected $partsDataType = 'array';
  /**
   * A list of task IDs that this message references for additional context.
   *
   * @var string[]
   */
  public $referenceTaskIds;
  /**
   * Required. Identifies the sender of the message.
   *
   * @var string
   */
  public $role;
  /**
   * Optional. The task id of the message. If set, the message will be
   * associated with the given task.
   *
   * @var string
   */
  public $taskId;

  /**
   * Optional. The context id of the message. If set, the message will be
   * associated with the given context.
   *
   * @param string $contextId
   */
  public function setContextId($contextId)
  {
    $this->contextId = $contextId;
  }
  /**
   * @return string
   */
  public function getContextId()
  {
    return $this->contextId;
  }
  /**
   * The URIs of extensions that are present or contributed to this Message.
   *
   * @param string[] $extensions
   */
  public function setExtensions($extensions)
  {
    $this->extensions = $extensions;
  }
  /**
   * @return string[]
   */
  public function getExtensions()
  {
    return $this->extensions;
  }
  /**
   * Required. The unique identifier (e.g. UUID) of the message. This is created
   * by the message creator.
   *
   * @param string $messageId
   */
  public function setMessageId($messageId)
  {
    $this->messageId = $messageId;
  }
  /**
   * @return string
   */
  public function getMessageId()
  {
    return $this->messageId;
  }
  /**
   * Optional. Any metadata to provide along with the message.
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
  /**
   * Required. Parts is the container of the message content.
   *
   * @param LfA2aV1Part[] $parts
   */
  public function setParts($parts)
  {
    $this->parts = $parts;
  }
  /**
   * @return LfA2aV1Part[]
   */
  public function getParts()
  {
    return $this->parts;
  }
  /**
   * A list of task IDs that this message references for additional context.
   *
   * @param string[] $referenceTaskIds
   */
  public function setReferenceTaskIds($referenceTaskIds)
  {
    $this->referenceTaskIds = $referenceTaskIds;
  }
  /**
   * @return string[]
   */
  public function getReferenceTaskIds()
  {
    return $this->referenceTaskIds;
  }
  /**
   * Required. Identifies the sender of the message.
   *
   * Accepted values: ROLE_UNSPECIFIED, ROLE_USER, ROLE_AGENT
   *
   * @param self::ROLE_* $role
   */
  public function setRole($role)
  {
    $this->role = $role;
  }
  /**
   * @return self::ROLE_*
   */
  public function getRole()
  {
    return $this->role;
  }
  /**
   * Optional. The task id of the message. If set, the message will be
   * associated with the given task.
   *
   * @param string $taskId
   */
  public function setTaskId($taskId)
  {
    $this->taskId = $taskId;
  }
  /**
   * @return string
   */
  public function getTaskId()
  {
    return $this->taskId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(LfA2aV1Message::class, 'Google_Service_CustomerEngagementSuite_LfA2aV1Message');
