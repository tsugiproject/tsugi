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

namespace Google\Service\DiscoveryEngine;

class GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpec extends \Google\Model
{
  protected $querySetSpecType = GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpecQuerySetSpec::class;
  protected $querySetSpecDataType = '';
  protected $searchRequestType = GoogleCloudDiscoveryengineV1alphaSearchRequest::class;
  protected $searchRequestDataType = '';

  /**
   * @param GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpecQuerySetSpec
   */
  public function setQuerySetSpec(GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpecQuerySetSpec $querySetSpec)
  {
    $this->querySetSpec = $querySetSpec;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpecQuerySetSpec
   */
  public function getQuerySetSpec()
  {
    return $this->querySetSpec;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1alphaSearchRequest
   */
  public function setSearchRequest(GoogleCloudDiscoveryengineV1alphaSearchRequest $searchRequest)
  {
    $this->searchRequest = $searchRequest;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1alphaSearchRequest
   */
  public function getSearchRequest()
  {
    return $this->searchRequest;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpec::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1alphaEvaluationEvaluationSpec');
