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

class GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource extends \Google\Model
{
  /**
   * Output only. The display name of the entry.
   *
   * @var string
   */
  public $displayName;
  /**
   * Output only. The entry name in the form of: projects/{project_id_or_number}
   * /locations/{location_id}/entryGroups/{entry_group_id}/entries/{entry_id}
   *
   * @var string
   */
  public $entry;
  /**
   * Output only. The entry type to represent the current characteristics of the
   * entry in the form of:
   * projects/{project_id_or_number}/locations/{location_id}/entryTypes/{entry-
   * type-id}.
   *
   * @var string
   */
  public $entryType;

  /**
   * Output only. The display name of the entry.
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Output only. The entry name in the form of: projects/{project_id_or_number}
   * /locations/{location_id}/entryGroups/{entry_group_id}/entries/{entry_id}
   *
   * @param string $entry
   */
  public function setEntry($entry)
  {
    $this->entry = $entry;
  }
  /**
   * @return string
   */
  public function getEntry()
  {
    return $this->entry;
  }
  /**
   * Output only. The entry type to represent the current characteristics of the
   * entry in the form of:
   * projects/{project_id_or_number}/locations/{location_id}/entryTypes/{entry-
   * type-id}.
   *
   * @param string $entryType
   */
  public function setEntryType($entryType)
  {
    $this->entryType = $entryType;
  }
  /**
   * @return string
   */
  public function getEntryType()
  {
    return $this->entryType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource');
