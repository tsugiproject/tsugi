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

namespace Google\Service\Firestore;

class ReadWrite extends \Google\Model
{
  /**
   * Start the transaction with the database-level default concurrency mode.
   */
  public const CONCURRENCY_MODE_CONCURRENCY_MODE_UNSPECIFIED = 'CONCURRENCY_MODE_UNSPECIFIED';
  /**
   * Use optimistic concurrency control for the new transaction.
   */
  public const CONCURRENCY_MODE_OPTIMISTIC = 'OPTIMISTIC';
  /**
   * Use pessimistic concurrency control for the new transaction.
   */
  public const CONCURRENCY_MODE_PESSIMISTIC = 'PESSIMISTIC';
  /**
   * Optional. The concurrency control mode to use for this transaction. A
   * database is able to use different concurrency modes for different
   * transactions simultaneously. 3rd party auth requests are only allowed to
   * create optimistic read-write transactions and must specify that here even
   * if the database-level setting is already configured to optimistic.
   *
   * @var string
   */
  public $concurrencyMode;
  /**
   * An optional transaction to retry.
   *
   * @var string
   */
  public $retryTransaction;

  /**
   * Optional. The concurrency control mode to use for this transaction. A
   * database is able to use different concurrency modes for different
   * transactions simultaneously. 3rd party auth requests are only allowed to
   * create optimistic read-write transactions and must specify that here even
   * if the database-level setting is already configured to optimistic.
   *
   * Accepted values: CONCURRENCY_MODE_UNSPECIFIED, OPTIMISTIC, PESSIMISTIC
   *
   * @param self::CONCURRENCY_MODE_* $concurrencyMode
   */
  public function setConcurrencyMode($concurrencyMode)
  {
    $this->concurrencyMode = $concurrencyMode;
  }
  /**
   * @return self::CONCURRENCY_MODE_*
   */
  public function getConcurrencyMode()
  {
    return $this->concurrencyMode;
  }
  /**
   * An optional transaction to retry.
   *
   * @param string $retryTransaction
   */
  public function setRetryTransaction($retryTransaction)
  {
    $this->retryTransaction = $retryTransaction;
  }
  /**
   * @return string
   */
  public function getRetryTransaction()
  {
    return $this->retryTransaction;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReadWrite::class, 'Google_Service_Firestore_ReadWrite');
