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

namespace Google\Service\OracleDatabase;

class GoldengateDeploymentLock extends \Google\Model
{
  /**
   * The lock type is unspecified.
   */
  public const TYPE_LOCK_TYPE_UNSPECIFIED = 'LOCK_TYPE_UNSPECIFIED';
  /**
   * The lock type is full.
   */
  public const TYPE_FULL = 'FULL';
  /**
   * The lock type is delete.
   */
  public const TYPE_DELETE = 'DELETE';
  /**
   * Output only. The compartment id.
   *
   * @var string
   */
  public $compartmentId;
  /**
   * Output only. The time created.
   *
   * @var string
   */
  public $createTime;
  /**
   * Output only. The message.
   *
   * @var string
   */
  public $message;
  /**
   * Output only. The related resource id.
   *
   * @var string
   */
  public $relatedResourceId;
  /**
   * Output only. The type of lock.
   *
   * @var string
   */
  public $type;

  /**
   * Output only. The compartment id.
   *
   * @param string $compartmentId
   */
  public function setCompartmentId($compartmentId)
  {
    $this->compartmentId = $compartmentId;
  }
  /**
   * @return string
   */
  public function getCompartmentId()
  {
    return $this->compartmentId;
  }
  /**
   * Output only. The time created.
   *
   * @param string $createTime
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * Output only. The message.
   *
   * @param string $message
   */
  public function setMessage($message)
  {
    $this->message = $message;
  }
  /**
   * @return string
   */
  public function getMessage()
  {
    return $this->message;
  }
  /**
   * Output only. The related resource id.
   *
   * @param string $relatedResourceId
   */
  public function setRelatedResourceId($relatedResourceId)
  {
    $this->relatedResourceId = $relatedResourceId;
  }
  /**
   * @return string
   */
  public function getRelatedResourceId()
  {
    return $this->relatedResourceId;
  }
  /**
   * Output only. The type of lock.
   *
   * Accepted values: LOCK_TYPE_UNSPECIFIED, FULL, DELETE
   *
   * @param self::TYPE_* $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return self::TYPE_*
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateDeploymentLock::class, 'Google_Service_OracleDatabase_GoldengateDeploymentLock');
