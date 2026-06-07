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

class GoogleCloudDataplexV1UpdateGlossaryCategoryRequest extends \Google\Model
{
  protected $categoryType = GoogleCloudDataplexV1GlossaryCategory::class;
  protected $categoryDataType = '';
  /**
   * Required. The list of fields to update.
   *
   * @var string
   */
  public $updateMask;

  /**
   * Required. The GlossaryCategory to update. The GlossaryCategory's name field
   * is used to identify the GlossaryCategory to update. Format: projects/{proje
   * ct_id_or_number}/locations/{location_id}/glossaries/{glossary_id}/categorie
   * s/{category_id}
   *
   * @param GoogleCloudDataplexV1GlossaryCategory $category
   */
  public function setCategory(GoogleCloudDataplexV1GlossaryCategory $category)
  {
    $this->category = $category;
  }
  /**
   * @return GoogleCloudDataplexV1GlossaryCategory
   */
  public function getCategory()
  {
    return $this->category;
  }
  /**
   * Required. The list of fields to update.
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
class_alias(GoogleCloudDataplexV1UpdateGlossaryCategoryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1UpdateGlossaryCategoryRequest');
