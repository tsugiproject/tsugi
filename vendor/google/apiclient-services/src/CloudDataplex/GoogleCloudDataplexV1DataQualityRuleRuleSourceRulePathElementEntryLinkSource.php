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

class GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource extends \Google\Model
{
  /**
   * Output only. The entry link name in the form of: projects/{project_id_or_nu
   * mber}/locations/{location_id}/entryGroups/{entry_group_id}/entryLinks/{entr
   * y_link_id}
   *
   * @var string
   */
  public $entryLink;
  /**
   * Output only. The entry link type to represent the current relationship
   * between the entry and the next entry in the path. In the form of: projects/
   * {project_id_or_number}/locations/{location_id}/entryLinkTypes/{entry_link_t
   * ype_id}
   *
   * @var string
   */
  public $entryLinkType;

  /**
   * Output only. The entry link name in the form of: projects/{project_id_or_nu
   * mber}/locations/{location_id}/entryGroups/{entry_group_id}/entryLinks/{entr
   * y_link_id}
   *
   * @param string $entryLink
   */
  public function setEntryLink($entryLink)
  {
    $this->entryLink = $entryLink;
  }
  /**
   * @return string
   */
  public function getEntryLink()
  {
    return $this->entryLink;
  }
  /**
   * Output only. The entry link type to represent the current relationship
   * between the entry and the next entry in the path. In the form of: projects/
   * {project_id_or_number}/locations/{location_id}/entryLinkTypes/{entry_link_t
   * ype_id}
   *
   * @param string $entryLinkType
   */
  public function setEntryLinkType($entryLinkType)
  {
    $this->entryLinkType = $entryLinkType;
  }
  /**
   * @return string
   */
  public function getEntryLinkType()
  {
    return $this->entryLinkType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource');
