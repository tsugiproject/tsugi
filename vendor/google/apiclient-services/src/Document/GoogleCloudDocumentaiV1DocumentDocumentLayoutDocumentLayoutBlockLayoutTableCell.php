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

class GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlockLayoutTableCell extends \Google\Collection
{
  protected $collection_key = 'blocks';
  protected $blocksType = GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlock::class;
  protected $blocksDataType = 'array';
  /**
   * @var int
   */
  public $colSpan;
  /**
   * @var int
   */
  public $rowSpan;

  /**
   * @param GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlock[]
   */
  public function setBlocks($blocks)
  {
    $this->blocks = $blocks;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlock[]
   */
  public function getBlocks()
  {
    return $this->blocks;
  }
  /**
   * @param int
   */
  public function setColSpan($colSpan)
  {
    $this->colSpan = $colSpan;
  }
  /**
   * @return int
   */
  public function getColSpan()
  {
    return $this->colSpan;
  }
  /**
   * @param int
   */
  public function setRowSpan($rowSpan)
  {
    $this->rowSpan = $rowSpan;
  }
  /**
   * @return int
   */
  public function getRowSpan()
  {
    return $this->rowSpan;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlockLayoutTableCell::class, 'Google_Service_Document_GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlockLayoutTableCell');
