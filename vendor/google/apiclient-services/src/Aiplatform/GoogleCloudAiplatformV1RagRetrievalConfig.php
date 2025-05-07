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

class GoogleCloudAiplatformV1RagRetrievalConfig extends \Google\Model
{
  protected $filterType = GoogleCloudAiplatformV1RagRetrievalConfigFilter::class;
  protected $filterDataType = '';
  /**
   * @var int
   */
  public $topK;

  /**
   * @param GoogleCloudAiplatformV1RagRetrievalConfigFilter
   */
  public function setFilter(GoogleCloudAiplatformV1RagRetrievalConfigFilter $filter)
  {
    $this->filter = $filter;
  }
  /**
   * @return GoogleCloudAiplatformV1RagRetrievalConfigFilter
   */
  public function getFilter()
  {
    return $this->filter;
  }
  /**
   * @param int
   */
  public function setTopK($topK)
  {
    $this->topK = $topK;
  }
  /**
   * @return int
   */
  public function getTopK()
  {
    return $this->topK;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1RagRetrievalConfig::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1RagRetrievalConfig');
