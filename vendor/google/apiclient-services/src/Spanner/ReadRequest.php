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

class ReadRequest extends \Google\Collection
{
  protected $collection_key = 'columns';
  /**
   * @var string[]
   */
  public $columns;
  /**
   * @var bool
   */
  public $dataBoostEnabled;
  protected $directedReadOptionsType = DirectedReadOptions::class;
  protected $directedReadOptionsDataType = '';
  /**
   * @var string
   */
  public $index;
  protected $keySetType = KeySet::class;
  protected $keySetDataType = '';
  /**
   * @var string
   */
  public $limit;
  /**
   * @var string
   */
  public $lockHint;
  /**
   * @var string
   */
  public $orderBy;
  /**
   * @var string
   */
  public $partitionToken;
  protected $requestOptionsType = RequestOptions::class;
  protected $requestOptionsDataType = '';
  /**
   * @var string
   */
  public $resumeToken;
  /**
   * @var string
   */
  public $table;
  protected $transactionType = TransactionSelector::class;
  protected $transactionDataType = '';

  /**
   * @param string[]
   */
  public function setColumns($columns)
  {
    $this->columns = $columns;
  }
  /**
   * @return string[]
   */
  public function getColumns()
  {
    return $this->columns;
  }
  /**
   * @param bool
   */
  public function setDataBoostEnabled($dataBoostEnabled)
  {
    $this->dataBoostEnabled = $dataBoostEnabled;
  }
  /**
   * @return bool
   */
  public function getDataBoostEnabled()
  {
    return $this->dataBoostEnabled;
  }
  /**
   * @param DirectedReadOptions
   */
  public function setDirectedReadOptions(DirectedReadOptions $directedReadOptions)
  {
    $this->directedReadOptions = $directedReadOptions;
  }
  /**
   * @return DirectedReadOptions
   */
  public function getDirectedReadOptions()
  {
    return $this->directedReadOptions;
  }
  /**
   * @param string
   */
  public function setIndex($index)
  {
    $this->index = $index;
  }
  /**
   * @return string
   */
  public function getIndex()
  {
    return $this->index;
  }
  /**
   * @param KeySet
   */
  public function setKeySet(KeySet $keySet)
  {
    $this->keySet = $keySet;
  }
  /**
   * @return KeySet
   */
  public function getKeySet()
  {
    return $this->keySet;
  }
  /**
   * @param string
   */
  public function setLimit($limit)
  {
    $this->limit = $limit;
  }
  /**
   * @return string
   */
  public function getLimit()
  {
    return $this->limit;
  }
  /**
   * @param string
   */
  public function setLockHint($lockHint)
  {
    $this->lockHint = $lockHint;
  }
  /**
   * @return string
   */
  public function getLockHint()
  {
    return $this->lockHint;
  }
  /**
   * @param string
   */
  public function setOrderBy($orderBy)
  {
    $this->orderBy = $orderBy;
  }
  /**
   * @return string
   */
  public function getOrderBy()
  {
    return $this->orderBy;
  }
  /**
   * @param string
   */
  public function setPartitionToken($partitionToken)
  {
    $this->partitionToken = $partitionToken;
  }
  /**
   * @return string
   */
  public function getPartitionToken()
  {
    return $this->partitionToken;
  }
  /**
   * @param RequestOptions
   */
  public function setRequestOptions(RequestOptions $requestOptions)
  {
    $this->requestOptions = $requestOptions;
  }
  /**
   * @return RequestOptions
   */
  public function getRequestOptions()
  {
    return $this->requestOptions;
  }
  /**
   * @param string
   */
  public function setResumeToken($resumeToken)
  {
    $this->resumeToken = $resumeToken;
  }
  /**
   * @return string
   */
  public function getResumeToken()
  {
    return $this->resumeToken;
  }
  /**
   * @param string
   */
  public function setTable($table)
  {
    $this->table = $table;
  }
  /**
   * @return string
   */
  public function getTable()
  {
    return $this->table;
  }
  /**
   * @param TransactionSelector
   */
  public function setTransaction(TransactionSelector $transaction)
  {
    $this->transaction = $transaction;
  }
  /**
   * @return TransactionSelector
   */
  public function getTransaction()
  {
    return $this->transaction;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReadRequest::class, 'Google_Service_Spanner_ReadRequest');
