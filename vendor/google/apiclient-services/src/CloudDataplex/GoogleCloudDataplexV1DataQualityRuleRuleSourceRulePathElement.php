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

class GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElement extends \Google\Model
{
  protected $entryLinkSourceType = GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource::class;
  protected $entryLinkSourceDataType = '';
  protected $entrySourceType = GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource::class;
  protected $entrySourceDataType = '';

  /**
   * Output only. Entry link source represents information about the entry link.
   *
   * @param GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource $entryLinkSource
   */
  public function setEntryLinkSource(GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource $entryLinkSource)
  {
    $this->entryLinkSource = $entryLinkSource;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntryLinkSource
   */
  public function getEntryLinkSource()
  {
    return $this->entryLinkSource;
  }
  /**
   * Output only. Entry source represents information about the related source
   * entry.
   *
   * @param GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource $entrySource
   */
  public function setEntrySource(GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource $entrySource)
  {
    $this->entrySource = $entrySource;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElementEntrySource
   */
  public function getEntrySource()
  {
    return $this->entrySource;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElement::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataQualityRuleRuleSourceRulePathElement');
