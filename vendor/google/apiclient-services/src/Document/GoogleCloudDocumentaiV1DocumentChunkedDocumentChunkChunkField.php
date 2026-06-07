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

class GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkChunkField extends \Google\Model
{
  protected $imageChunkFieldType = GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkImageChunkField::class;
  protected $imageChunkFieldDataType = '';
  protected $tableChunkFieldType = GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkTableChunkField::class;
  protected $tableChunkFieldDataType = '';

  /**
   * The image chunk field in the chunk.
   *
   * @param GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkImageChunkField $imageChunkField
   */
  public function setImageChunkField(GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkImageChunkField $imageChunkField)
  {
    $this->imageChunkField = $imageChunkField;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkImageChunkField
   */
  public function getImageChunkField()
  {
    return $this->imageChunkField;
  }
  /**
   * The table chunk field in the chunk.
   *
   * @param GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkTableChunkField $tableChunkField
   */
  public function setTableChunkField(GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkTableChunkField $tableChunkField)
  {
    $this->tableChunkField = $tableChunkField;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkTableChunkField
   */
  public function getTableChunkField()
  {
    return $this->tableChunkField;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkChunkField::class, 'Google_Service_Document_GoogleCloudDocumentaiV1DocumentChunkedDocumentChunkChunkField');
