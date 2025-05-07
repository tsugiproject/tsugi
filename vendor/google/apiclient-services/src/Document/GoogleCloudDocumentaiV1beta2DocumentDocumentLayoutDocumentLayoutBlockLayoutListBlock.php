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

class GoogleCloudDocumentaiV1beta2DocumentDocumentLayoutDocumentLayoutBlockLayoutListBlock extends \Google\Collection
{
  protected $collection_key = 'listEntries';
  protected $listEntriesType = GoogleCloudDocumentaiV1beta2DocumentDocumentLayoutDocumentLayoutBlockLayoutListEntry::class;
  protected $listEntriesDataType = 'array';
  /**
   * @var string
   */
  public $type;

  /**
   * @param GoogleCloudDocumentaiV1beta2DocumentDocumentLayoutDocumentLayoutBlockLayoutListEntry[]
   */
  public function setListEntries($listEntries)
  {
    $this->listEntries = $listEntries;
  }
  /**
   * @return GoogleCloudDocumentaiV1beta2DocumentDocumentLayoutDocumentLayoutBlockLayoutListEntry[]
   */
  public function getListEntries()
  {
    return $this->listEntries;
  }
  /**
   * @param string
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return string
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDocumentaiV1beta2DocumentDocumentLayoutDocumentLayoutBlockLayoutListBlock::class, 'Google_Service_Document_GoogleCloudDocumentaiV1beta2DocumentDocumentLayoutDocumentLayoutBlockLayoutListBlock');
