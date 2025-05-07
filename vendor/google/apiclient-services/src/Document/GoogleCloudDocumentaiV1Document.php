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

namespace Google\Service\Document;

class GoogleCloudDocumentaiV1Document extends \Google\Collection
{
  protected $collection_key = 'textStyles';
  protected $chunkedDocumentType = GoogleCloudDocumentaiV1DocumentChunkedDocument::class;
  protected $chunkedDocumentDataType = '';
  /**
   * @var string
   */
  public $content;
  protected $documentLayoutType = GoogleCloudDocumentaiV1DocumentDocumentLayout::class;
  protected $documentLayoutDataType = '';
  protected $entitiesType = GoogleCloudDocumentaiV1DocumentEntity::class;
  protected $entitiesDataType = 'array';
  protected $entityRelationsType = GoogleCloudDocumentaiV1DocumentEntityRelation::class;
  protected $entityRelationsDataType = 'array';
  protected $errorType = GoogleRpcStatus::class;
  protected $errorDataType = '';
  /**
   * @var string
   */
  public $mimeType;
  protected $pagesType = GoogleCloudDocumentaiV1DocumentPage::class;
  protected $pagesDataType = 'array';
  protected $revisionsType = GoogleCloudDocumentaiV1DocumentRevision::class;
  protected $revisionsDataType = 'array';
  protected $shardInfoType = GoogleCloudDocumentaiV1DocumentShardInfo::class;
  protected $shardInfoDataType = '';
  /**
   * @var string
   */
  public $text;
  protected $textChangesType = GoogleCloudDocumentaiV1DocumentTextChange::class;
  protected $textChangesDataType = 'array';
  protected $textStylesType = GoogleCloudDocumentaiV1DocumentStyle::class;
  protected $textStylesDataType = 'array';
  /**
   * @var string
   */
  public $uri;

  /**
   * @param GoogleCloudDocumentaiV1DocumentChunkedDocument
   */
  public function setChunkedDocument(GoogleCloudDocumentaiV1DocumentChunkedDocument $chunkedDocument)
  {
    $this->chunkedDocument = $chunkedDocument;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentChunkedDocument
   */
  public function getChunkedDocument()
  {
    return $this->chunkedDocument;
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
   * @param GoogleCloudDocumentaiV1DocumentDocumentLayout
   */
  public function setDocumentLayout(GoogleCloudDocumentaiV1DocumentDocumentLayout $documentLayout)
  {
    $this->documentLayout = $documentLayout;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentDocumentLayout
   */
  public function getDocumentLayout()
  {
    return $this->documentLayout;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentEntity[]
   */
  public function setEntities($entities)
  {
    $this->entities = $entities;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentEntity[]
   */
  public function getEntities()
  {
    return $this->entities;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentEntityRelation[]
   */
  public function setEntityRelations($entityRelations)
  {
    $this->entityRelations = $entityRelations;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentEntityRelation[]
   */
  public function getEntityRelations()
  {
    return $this->entityRelations;
  }
  /**
   * @param GoogleRpcStatus
   */
  public function setError(GoogleRpcStatus $error)
  {
    $this->error = $error;
  }
  /**
   * @return GoogleRpcStatus
   */
  public function getError()
  {
    return $this->error;
  }
  /**
   * @param string
   */
  public function setMimeType($mimeType)
  {
    $this->mimeType = $mimeType;
  }
  /**
   * @return string
   */
  public function getMimeType()
  {
    return $this->mimeType;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentPage[]
   */
  public function setPages($pages)
  {
    $this->pages = $pages;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentPage[]
   */
  public function getPages()
  {
    return $this->pages;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentRevision[]
   */
  public function setRevisions($revisions)
  {
    $this->revisions = $revisions;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentRevision[]
   */
  public function getRevisions()
  {
    return $this->revisions;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentShardInfo
   */
  public function setShardInfo(GoogleCloudDocumentaiV1DocumentShardInfo $shardInfo)
  {
    $this->shardInfo = $shardInfo;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentShardInfo
   */
  public function getShardInfo()
  {
    return $this->shardInfo;
  }
  /**
   * @param string
   */
  public function setText($text)
  {
    $this->text = $text;
  }
  /**
   * @return string
   */
  public function getText()
  {
    return $this->text;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentTextChange[]
   */
  public function setTextChanges($textChanges)
  {
    $this->textChanges = $textChanges;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentTextChange[]
   */
  public function getTextChanges()
  {
    return $this->textChanges;
  }
  /**
   * @param GoogleCloudDocumentaiV1DocumentStyle[]
   */
  public function setTextStyles($textStyles)
  {
    $this->textStyles = $textStyles;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentStyle[]
   */
  public function getTextStyles()
  {
    return $this->textStyles;
  }
  /**
   * @param string
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDocumentaiV1Document::class, 'Google_Service_Document_GoogleCloudDocumentaiV1Document');
