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

class GoogleCloudDataplexV1CreateGlossaryRequest extends \Google\Model
{
  protected $glossaryType = GoogleCloudDataplexV1Glossary::class;
  protected $glossaryDataType = '';
  /**
   * Required. Glossary ID: Glossary identifier.
   *
   * @var string
   */
  public $glossaryId;
  /**
   * Required. The parent resource where this Glossary will be created. Format:
   * projects/{project_id_or_number}/locations/{location_id} where location_id
   * refers to a Google Cloud region.
   *
   * @var string
   */
  public $parent;
  /**
   * Optional. Validates the request without actually creating the Glossary.
   * Default: false.
   *
   * @var bool
   */
  public $validateOnly;

  /**
   * Required. The Glossary to create.
   *
   * @param GoogleCloudDataplexV1Glossary $glossary
   */
  public function setGlossary(GoogleCloudDataplexV1Glossary $glossary)
  {
    $this->glossary = $glossary;
  }
  /**
   * @return GoogleCloudDataplexV1Glossary
   */
  public function getGlossary()
  {
    return $this->glossary;
  }
  /**
   * Required. Glossary ID: Glossary identifier.
   *
   * @param string $glossaryId
   */
  public function setGlossaryId($glossaryId)
  {
    $this->glossaryId = $glossaryId;
  }
  /**
   * @return string
   */
  public function getGlossaryId()
  {
    return $this->glossaryId;
  }
  /**
   * Required. The parent resource where this Glossary will be created. Format:
   * projects/{project_id_or_number}/locations/{location_id} where location_id
   * refers to a Google Cloud region.
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
   * Optional. Validates the request without actually creating the Glossary.
   * Default: false.
   *
   * @param bool $validateOnly
   */
  public function setValidateOnly($validateOnly)
  {
    $this->validateOnly = $validateOnly;
  }
  /**
   * @return bool
   */
  public function getValidateOnly()
  {
    return $this->validateOnly;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1CreateGlossaryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1CreateGlossaryRequest');
