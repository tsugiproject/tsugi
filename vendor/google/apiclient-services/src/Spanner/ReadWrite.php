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

namespace Google\Service\Spanner;

class ReadWrite extends \Google\Model
{
  /**
   * @var string
   */
  public $multiplexedSessionPreviousTransactionId;
  /**
   * @var string
   */
  public $readLockMode;

  /**
   * @param string
   */
  public function setMultiplexedSessionPreviousTransactionId($multiplexedSessionPreviousTransactionId)
  {
    $this->multiplexedSessionPreviousTransactionId = $multiplexedSessionPreviousTransactionId;
  }
  /**
   * @return string
   */
  public function getMultiplexedSessionPreviousTransactionId()
  {
    return $this->multiplexedSessionPreviousTransactionId;
  }
  /**
   * @param string
   */
  public function setReadLockMode($readLockMode)
  {
    $this->readLockMode = $readLockMode;
  }
  /**
   * @return string
   */
  public function getReadLockMode()
  {
    return $this->readLockMode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReadWrite::class, 'Google_Service_Spanner_ReadWrite');
