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

class GoogleCloudDataplexV1CreateEntryLinkRequest extends \Google\Model
{
  protected $entryLinkType = GoogleCloudDataplexV1EntryLink::class;
  protected $entryLinkDataType = '';
  /**
   * Required. Entry Link identifier * Must contain only lowercase letters,
   * numbers and hyphens. * Must start with a letter. * Must be between 1-63
   * characters. * Must end with a number or a letter. * Must be unique within
   * the EntryGroup.
   *
   * @var string
   */
  public $entryLinkId;
  /**
   * Required. The resource name of the parent Entry Group: projects/{project_id
   * _or_number}/locations/{location_id}/entryGroups/{entry_group_id}.
   *
   * @var string
   */
  public $parent;

  /**
   * Required. Entry Link resource.
   *
   * @param GoogleCloudDataplexV1EntryLink $entryLink
   */
  public function setEntryLink(GoogleCloudDataplexV1EntryLink $entryLink)
  {
    $this->entryLink = $entryLink;
  }
  /**
   * @return GoogleCloudDataplexV1EntryLink
   */
  public function getEntryLink()
  {
    return $this->entryLink;
  }
  /**
   * Required. Entry Link identifier * Must contain only lowercase letters,
   * numbers and hyphens. * Must start with a letter. * Must be between 1-63
   * characters. * Must end with a number or a letter. * Must be unique within
   * the EntryGroup.
   *
   * @param string $entryLinkId
   */
  public function setEntryLinkId($entryLinkId)
  {
    $this->entryLinkId = $entryLinkId;
  }
  /**
   * @return string
   */
  public function getEntryLinkId()
  {
    return $this->entryLinkId;
  }
  /**
   * Required. The resource name of the parent Entry Group: projects/{project_id
   * _or_number}/locations/{location_id}/entryGroups/{entry_group_id}.
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
class_alias(GoogleCloudDataplexV1CreateEntryLinkRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1CreateEntryLinkRequest');
