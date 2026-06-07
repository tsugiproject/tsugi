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
   * Default value. * If isolation level is SERIALIZABLE, locking semantics
   * default to `PESSIMISTIC`. * If isolation level is REPEATABLE_READ, locking
   * semantics default to `OPTIMISTIC`. * See [Concurrency
   * control](https://cloud.google.com/spanner/docs/concurrency-control) for
   * more details.
   */
  public const READ_LOCK_MODE_READ_LOCK_MODE_UNSPECIFIED = 'READ_LOCK_MODE_UNSPECIFIED';
  /**
   * Pessimistic lock mode. Lock acquisition behavior depends on the isolation
   * level in use. In SERIALIZABLE isolation, reads and writes acquire necessary
   * locks during transaction statement execution. In REPEATABLE_READ isolation,
   * reads that explicitly request to be locked and writes acquire locks. See
   * [Concurrency control](https://cloud.google.com/spanner/docs/concurrency-
   * control) for details on the types of locks acquired at each transaction
   * step.
   */
  public const READ_LOCK_MODE_PESSIMISTIC = 'PESSIMISTIC';
  /**
   * Optimistic lock mode. Lock acquisition behavior depends on the isolation
   * level in use. In both SERIALIZABLE and REPEATABLE_READ isolation, reads and
   * writes do not acquire locks during transaction statement execution. See
   * [Concurrency control](https://cloud.google.com/spanner/docs/concurrency-
   * control) for details on how the guarantees of each isolation level are
   * provided at commit time.
   */
  public const READ_LOCK_MODE_OPTIMISTIC = 'OPTIMISTIC';
  /**
   * Optional. Clients should pass the transaction ID of the previous
   * transaction attempt that was aborted if this transaction is being executed
   * on a multiplexed session.
   *
   * @var string
   */
  public $multiplexedSessionPreviousTransactionId;
  /**
   * The read lock mode for the transaction.
   *
   * @var string
   */
  public $readLockMode;

  /**
   * Optional. Clients should pass the transaction ID of the previous
   * transaction attempt that was aborted if this transaction is being executed
   * on a multiplexed session.
   *
   * @param string $multiplexedSessionPreviousTransactionId
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
   * The read lock mode for the transaction.
   *
   * Accepted values: READ_LOCK_MODE_UNSPECIFIED, PESSIMISTIC, OPTIMISTIC
   *
   * @param self::READ_LOCK_MODE_* $readLockMode
   */
  public function setReadLockMode($readLockMode)
  {
    $this->readLockMode = $readLockMode;
  }
  /**
   * @return self::READ_LOCK_MODE_*
   */
  public function getReadLockMode()
  {
    return $this->readLockMode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReadWrite::class, 'Google_Service_Spanner_ReadWrite');
