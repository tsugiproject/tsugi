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

class BeginTransactionRequest extends \Google\Model
{
  protected $mutationKeyType = Mutation::class;
  protected $mutationKeyDataType = '';
  protected $optionsType = TransactionOptions::class;
  protected $optionsDataType = '';
  protected $requestOptionsType = RequestOptions::class;
  protected $requestOptionsDataType = '';

  /**
   * @param Mutation
   */
  public function setMutationKey(Mutation $mutationKey)
  {
    $this->mutationKey = $mutationKey;
  }
  /**
   * @return Mutation
   */
  public function getMutationKey()
  {
    return $this->mutationKey;
  }
  /**
   * @param TransactionOptions
   */
  public function setOptions(TransactionOptions $options)
  {
    $this->options = $options;
  }
  /**
   * @return TransactionOptions
   */
  public function getOptions()
  {
    return $this->options;
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BeginTransactionRequest::class, 'Google_Service_Spanner_BeginTransactionRequest');
