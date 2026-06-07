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

class GoogleCloudDataplexV1ModifyEntryRequest extends \Google\Collection
{
  protected $collection_key = 'aspectKeys';
  /**
   * Optional. The aspect keys which the service should modify. It supports the
   * following syntaxes: - matches an aspect of the given type and empty path.
   * @path - matches an aspect of the given type and specified path. For
   * example, to attach an aspect to a field that is specified by the schema
   * aspect, the path should have the format Schema.. @* - matches aspects of
   * the given type for all paths. *@path - matches aspects of all types on the
   * given path.The service will not remove existing aspects matching the syntax
   * unless delete_missing_aspects is set to true.If this field is left empty,
   * the service treats it as specifying exactly those Aspects present in the
   * request.
   *
   * @var string[]
   */
  public $aspectKeys;
  /**
   * Optional. If set to true, any aspects not specified in the request will be
   * deleted. The default is false.
   *
   * @var bool
   */
  public $deleteMissingAspects;
  protected $entryType = GoogleCloudDataplexV1Entry::class;
  protected $entryDataType = '';
  /**
   * Optional. Mask of fields to update. To update Aspects, the update_mask must
   * contain the value "aspects".If the update_mask is empty, the service will
   * update all modifiable fields present in the request.
   *
   * @var string
   */
  public $updateMask;

  /**
   * Optional. The aspect keys which the service should modify. It supports the
   * following syntaxes: - matches an aspect of the given type and empty path.
   * @path - matches an aspect of the given type and specified path. For
   * example, to attach an aspect to a field that is specified by the schema
   * aspect, the path should have the format Schema.. @* - matches aspects of
   * the given type for all paths. *@path - matches aspects of all types on the
   * given path.The service will not remove existing aspects matching the syntax
   * unless delete_missing_aspects is set to true.If this field is left empty,
   * the service treats it as specifying exactly those Aspects present in the
   * request.
   *
   * @param string[] $aspectKeys
   */
  public function setAspectKeys($aspectKeys)
  {
    $this->aspectKeys = $aspectKeys;
  }
  /**
   * @return string[]
   */
  public function getAspectKeys()
  {
    return $this->aspectKeys;
  }
  /**
   * Optional. If set to true, any aspects not specified in the request will be
   * deleted. The default is false.
   *
   * @param bool $deleteMissingAspects
   */
  public function setDeleteMissingAspects($deleteMissingAspects)
  {
    $this->deleteMissingAspects = $deleteMissingAspects;
  }
  /**
   * @return bool
   */
  public function getDeleteMissingAspects()
  {
    return $this->deleteMissingAspects;
  }
  /**
   * Required. The entry to modify.
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
   * Optional. Mask of fields to update. To update Aspects, the update_mask must
   * contain the value "aspects".If the update_mask is empty, the service will
   * update all modifiable fields present in the request.
   *
   * @param string $updateMask
   */
  public function setUpdateMask($updateMask)
  {
    $this->updateMask = $updateMask;
  }
  /**
   * @return string
   */
  public function getUpdateMask()
  {
    return $this->updateMask;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1ModifyEntryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1ModifyEntryRequest');
