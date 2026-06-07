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

namespace Google\Service\PostmasterTools;

class BatchQueryDomainStatsResult extends \Google\Model
{
  protected $errorType = Status::class;
  protected $errorDataType = '';
  protected $responseType = QueryDomainStatsResponse::class;
  protected $responseDataType = '';

  /**
   * The error status if the individual query failed.
   *
   * @param Status $error
   */
  public function setError(Status $error)
  {
    $this->error = $error;
  }
  /**
   * @return Status
   */
  public function getError()
  {
    return $this->error;
  }
  /**
   * The successful response for the individual query.
   *
   * @param QueryDomainStatsResponse $response
   */
  public function setResponse(QueryDomainStatsResponse $response)
  {
    $this->response = $response;
  }
  /**
   * @return QueryDomainStatsResponse
   */
  public function getResponse()
  {
    return $this->response;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(BatchQueryDomainStatsResult::class, 'Google_Service_PostmasterTools_BatchQueryDomainStatsResult');
