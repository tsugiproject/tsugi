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

class GoogleCloudDataplexV1CreateGlossaryCategoryRequest extends \Google\Model
{
  protected $categoryType = GoogleCloudDataplexV1GlossaryCategory::class;
  protected $categoryDataType = '';
  /**
   * Required. GlossaryCategory identifier.
   *
   * @var string
   */
  public $categoryId;
  /**
   * Required. The parent resource where this GlossaryCategory will be created.
   * Format: projects/{project_id_or_number}/locations/{location_id}/glossaries/
   * {glossary_id} where locationId refers to a Google Cloud region.
   *
   * @var string
   */
  public $parent;

  /**
   * Required. The GlossaryCategory to create.
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
   * Required. GlossaryCategory identifier.
   *
   * @param string $categoryId
   */
  public function setCategoryId($categoryId)
  {
    $this->categoryId = $categoryId;
  }
  /**
   * @return string
   */
  public function getCategoryId()
  {
    return $this->categoryId;
  }
  /**
   * Required. The parent resource where this GlossaryCategory will be created.
   * Format: projects/{project_id_or_number}/locations/{location_id}/glossaries/
   * {glossary_id} where locationId refers to a Google Cloud region.
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
class_alias(GoogleCloudDataplexV1CreateGlossaryCategoryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1CreateGlossaryCategoryRequest');
