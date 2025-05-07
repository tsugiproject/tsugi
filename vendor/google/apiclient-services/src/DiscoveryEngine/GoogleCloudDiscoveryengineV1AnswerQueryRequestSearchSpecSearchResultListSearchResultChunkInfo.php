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

class GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfo extends \Google\Model
{
  /**
   * @var string
   */
  public $chunk;
  /**
   * @var string
   */
  public $content;
  protected $documentMetadataType = GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfoDocumentMetadata::class;
  protected $documentMetadataDataType = '';

  /**
   * @param string
   */
  public function setChunk($chunk)
  {
    $this->chunk = $chunk;
  }
  /**
   * @return string
   */
  public function getChunk()
  {
    return $this->chunk;
  }
  /**
   * @param string
   */
  public function setContent($content)
  {
    $this->content = $content;
  }
  /**
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }
  /**
   * @param GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfoDocumentMetadata
   */
  public function setDocumentMetadata(GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfoDocumentMetadata $documentMetadata)
  {
    $this->documentMetadata = $documentMetadata;
  }
  /**
   * @return GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfoDocumentMetadata
   */
  public function getDocumentMetadata()
  {
    return $this->documentMetadata;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfo::class, 'Google_Service_DiscoveryEngine_GoogleCloudDiscoveryengineV1AnswerQueryRequestSearchSpecSearchResultListSearchResultChunkInfo');
