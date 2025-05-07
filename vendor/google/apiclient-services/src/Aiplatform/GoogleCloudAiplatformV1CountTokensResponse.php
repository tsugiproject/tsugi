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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1CountTokensResponse extends \Google\Collection
{
  protected $collection_key = 'promptTokensDetails';
  protected $promptTokensDetailsType = GoogleCloudAiplatformV1ModalityTokenCount::class;
  protected $promptTokensDetailsDataType = 'array';
  /**
   * @var int
   */
  public $totalBillableCharacters;
  /**
   * @var int
   */
  public $totalTokens;

  /**
   * @param GoogleCloudAiplatformV1ModalityTokenCount[]
   */
  public function setPromptTokensDetails($promptTokensDetails)
  {
    $this->promptTokensDetails = $promptTokensDetails;
  }
  /**
   * @return GoogleCloudAiplatformV1ModalityTokenCount[]
   */
  public function getPromptTokensDetails()
  {
    return $this->promptTokensDetails;
  }
  /**
   * @param int
   */
  public function setTotalBillableCharacters($totalBillableCharacters)
  {
    $this->totalBillableCharacters = $totalBillableCharacters;
  }
  /**
   * @return int
   */
  public function getTotalBillableCharacters()
  {
    return $this->totalBillableCharacters;
  }
  /**
   * @param int
   */
  public function setTotalTokens($totalTokens)
  {
    $this->totalTokens = $totalTokens;
  }
  /**
   * @return int
   */
  public function getTotalTokens()
  {
    return $this->totalTokens;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1CountTokensResponse::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1CountTokensResponse');
