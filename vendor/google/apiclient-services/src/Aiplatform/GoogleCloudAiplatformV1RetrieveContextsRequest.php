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

class GoogleCloudAiplatformV1RetrieveContextsRequest extends \Google\Model
{
  protected $queryType = GoogleCloudAiplatformV1RagQuery::class;
  protected $queryDataType = '';
  protected $vertexRagStoreType = GoogleCloudAiplatformV1RetrieveContextsRequestVertexRagStore::class;
  protected $vertexRagStoreDataType = '';

  /**
   * @param GoogleCloudAiplatformV1RagQuery
   */
  public function setQuery(GoogleCloudAiplatformV1RagQuery $query)
  {
    $this->query = $query;
  }
  /**
   * @return GoogleCloudAiplatformV1RagQuery
   */
  public function getQuery()
  {
    return $this->query;
  }
  /**
   * @param GoogleCloudAiplatformV1RetrieveContextsRequestVertexRagStore
   */
  public function setVertexRagStore(GoogleCloudAiplatformV1RetrieveContextsRequestVertexRagStore $vertexRagStore)
  {
    $this->vertexRagStore = $vertexRagStore;
  }
  /**
   * @return GoogleCloudAiplatformV1RetrieveContextsRequestVertexRagStore
   */
  public function getVertexRagStore()
  {
    return $this->vertexRagStore;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1RetrieveContextsRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1RetrieveContextsRequest');
