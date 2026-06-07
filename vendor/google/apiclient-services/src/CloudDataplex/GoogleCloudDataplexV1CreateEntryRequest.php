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

namespace Google\Service\CloudDataplex;

class GoogleCloudDataplexV1CreateEntryRequest extends \Google\Model
{
  protected $entryType = GoogleCloudDataplexV1Entry::class;
  protected $entryDataType = '';
  /**
   * Required. Entry identifier. It has to be unique within an Entry
   * Group.Entries corresponding to Google Cloud resources use an Entry ID
   * format based on full resource names
   * (https://cloud.google.com/apis/design/resource_names#full_resource_name).
   * The format is a full resource name of the resource without the prefix
   * double slashes in the API service name part of the full resource name. This
   * allows retrieval of entries using their associated resource name.For
   * example, if the full resource name of a resource is
   * //library.googleapis.com/shelves/shelf1/books/book2, then the suggested
   * entry_id is library.googleapis.com/shelves/shelf1/books/book2.It is also
   * suggested to follow the same convention for entries corresponding to
   * resources from providers or systems other than Google Cloud.The maximum
   * size of the field is 4000 characters.
   *
   * @var string
   */
  public $entryId;
  /**
   * Required. The resource name of the parent Entry Group:
   * projects/{project}/locations/{location}/entryGroups/{entry_group}.
   *
   * @var string
   */
  public $parent;

  /**
   * Required. Entry resource.
   *
   * @param GoogleCloudDataplexV1Entry $entry
   */
  public function setEntry(GoogleCloudDataplexV1Entry $entry)
  {
    $this->entry = $entry;
  }
  /**
   * @return GoogleCloudDataplexV1Entry
   */
  public function getEntry()
  {
    return $this->entry;
  }
  /**
   * Required. Entry identifier. It has to be unique within an Entry
   * Group.Entries corresponding to Google Cloud resources use an Entry ID
   * format based on full resource names
   * (https://cloud.google.com/apis/design/resource_names#full_resource_name).
   * The format is a full resource name of the resource without the prefix
   * double slashes in the API service name part of the full resource name. This
   * allows retrieval of entries using their associated resource name.For
   * example, if the full resource name of a resource is
   * //library.googleapis.com/shelves/shelf1/books/book2, then the suggested
   * entry_id is library.googleapis.com/shelves/shelf1/books/book2.It is also
   * suggested to follow the same convention for entries corresponding to
   * resources from providers or systems other than Google Cloud.The maximum
   * size of the field is 4000 characters.
   *
   * @param string $entryId
   */
  public function setEntryId($entryId)
  {
    $this->entryId = $entryId;
  }
  /**
   * @return string
   */
  public function getEntryId()
  {
    return $this->entryId;
  }
  /**
   * Required. The resource name of the parent Entry Group:
   * projects/{project}/locations/{location}/entryGroups/{entry_group}.
   *
   * @param string $parent
   */
  public function setParent($parent)
  {
    $this->parent = $parent;
  }
  /**
   * @return string
   */
  public function getParent()
  {
    return $this->parent;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1CreateEntryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1CreateEntryRequest');
