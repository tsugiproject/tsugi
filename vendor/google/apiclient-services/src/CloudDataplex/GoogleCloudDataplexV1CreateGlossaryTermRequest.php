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

class GoogleCloudDataplexV1CreateGlossaryTermRequest extends \Google\Model
{
  /**
   * Required. The parent resource where the GlossaryTerm will be created.
   * Format: projects/{project_id_or_number}/locations/{location_id}/glossaries/
   * {glossary_id} where location_id refers to a Google Cloud region.
   *
   * @var string
   */
  public $parent;
  protected $termType = GoogleCloudDataplexV1GlossaryTerm::class;
  protected $termDataType = '';
  /**
   * Required. GlossaryTerm identifier.
   *
   * @var string
   */
  public $termId;

  /**
   * Required. The parent resource where the GlossaryTerm will be created.
   * Format: projects/{project_id_or_number}/locations/{location_id}/glossaries/
   * {glossary_id} where location_id refers to a Google Cloud region.
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
  /**
   * Required. The GlossaryTerm to create.
   *
   * @param GoogleCloudDataplexV1GlossaryTerm $term
   */
  public function setTerm(GoogleCloudDataplexV1GlossaryTerm $term)
  {
    $this->term = $term;
  }
  /**
   * @return GoogleCloudDataplexV1GlossaryTerm
   */
  public function getTerm()
  {
    return $this->term;
  }
  /**
   * Required. GlossaryTerm identifier.
   *
   * @param string $termId
   */
  public function setTermId($termId)
  {
    $this->termId = $termId;
  }
  /**
   * @return string
   */
  public function getTermId()
  {
    return $this->termId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1CreateGlossaryTermRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1CreateGlossaryTermRequest');
