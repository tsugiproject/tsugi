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

class CommitRequest extends \Google\Collection
{
  protected $collection_key = 'mutations';
  /**
   * @var string
   */
  public $maxCommitDelay;
  protected $mutationsType = Mutation::class;
  protected $mutationsDataType = 'array';
  protected $precommitTokenType = MultiplexedSessionPrecommitToken::class;
  protected $precommitTokenDataType = '';
  protected $requestOptionsType = RequestOptions::class;
  protected $requestOptionsDataType = '';
  /**
   * @var bool
   */
  public $returnCommitStats;
  protected $singleUseTransactionType = TransactionOptions::class;
  protected $singleUseTransactionDataType = '';
  /**
   * @var string
   */
  public $transactionId;

  /**
   * @param string
   */
  public function setMaxCommitDelay($maxCommitDelay)
  {
    $this->maxCommitDelay = $maxCommitDelay;
  }
  /**
   * @return string
   */
  public function getMaxCommitDelay()
  {
    return $this->maxCommitDelay;
  }
  /**
   * @param Mutation[]
   */
  public function setMutations($mutations)
  {
    $this->mutations = $mutations;
  }
  /**
   * @return Mutation[]
   */
  public function getMutations()
  {
    return $this->mutations;
  }
  /**
   * @param MultiplexedSessionPrecommitToken
   */
  public function setPrecommitToken(MultiplexedSessionPrecommitToken $precommitToken)
  {
    $this->precommitToken = $precommitToken;
  }
  /**
   * @return MultiplexedSessionPrecommitToken
   */
  public function getPrecommitToken()
  {
    return $this->precommitToken;
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
   * @param bool
   */
  public function setReturnCommitStats($returnCommitStats)
  {
    $this->returnCommitStats = $returnCommitStats;
  }
  /**
   * @return bool
   */
  public function getReturnCommitStats()
  {
    return $this->returnCommitStats;
  }
  /**
   * @param TransactionOptions
   */
  public function setSingleUseTransaction(TransactionOptions $singleUseTransaction)
  {
    $this->singleUseTransaction = $singleUseTransaction;
  }
  /**
   * @return TransactionOptions
   */
  public function getSingleUseTransaction()
  {
    return $this->singleUseTransaction;
  }
  /**
   * @param string
   */
  public function setTransactionId($transactionId)
  {
    $this->transactionId = $transactionId;
  }
  /**
   * @return string
   */
  public function getTransactionId()
  {
    return $this->transactionId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CommitRequest::class, 'Google_Service_Spanner_CommitRequest');
