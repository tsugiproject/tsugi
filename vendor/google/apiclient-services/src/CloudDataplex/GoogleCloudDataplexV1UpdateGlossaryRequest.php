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

class GoogleCloudDataplexV1UpdateGlossaryRequest extends \Google\Model
{
  protected $glossaryType = GoogleCloudDataplexV1Glossary::class;
  protected $glossaryDataType = '';
  /**
   * Required. The list of fields to update.
   *
   * @var string
   */
  public $updateMask;
  /**
   * Optional. Validates the request without actually updating the Glossary.
   * Default: false.
   *
   * @var bool
   */
  public $validateOnly;

  /**
   * Required. The Glossary to update. The Glossary's name field is used to
   * identify the Glossary to update. Format: projects/{project_id_or_number}/lo
   * cations/{location_id}/glossaries/{glossary_id}
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
  /**
   * Optional. Validates the request without actually updating the Glossary.
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
class_alias(GoogleCloudDataplexV1UpdateGlossaryRequest::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1UpdateGlossaryRequest');
