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

class GoogleCloudDataplexV1DataQualityRuleTemplate extends \Google\Collection
{
  protected $collection_key = 'sqlCollection';
  /**
   * Output only. A list of features or properties supported by this rule
   * template.
   *
   * @var string[]
   */
  public $capabilities;
  /**
   * Output only. The dimension a rule template belongs to. Rule level results
   * are also aggregated at the dimension level.
   *
   * @var string
   */
  public $dimension;
  protected $inputParametersType = GoogleCloudDataplexV1DataQualityRuleTemplateParameterDescription::class;
  protected $inputParametersDataType = 'map';
  /**
   * Output only. The name of the rule template in the format: projects/{project
   * _id_or_number}/locations/{location_id}/entryGroups/{entry_group_id}/entries
   * /{entry_id}
   *
   * @var string
   */
  public $name;
  protected $sqlCollectionType = GoogleCloudDataplexV1DataQualityRuleTemplateSql::class;
  protected $sqlCollectionDataType = 'array';

  /**
   * Output only. A list of features or properties supported by this rule
   * template.
   *
   * @param string[] $capabilities
   */
  public function setCapabilities($capabilities)
  {
    $this->capabilities = $capabilities;
  }
  /**
   * @return string[]
   */
  public function getCapabilities()
  {
    return $this->capabilities;
  }
  /**
   * Output only. The dimension a rule template belongs to. Rule level results
   * are also aggregated at the dimension level.
   *
   * @param string $dimension
   */
  public function setDimension($dimension)
  {
    $this->dimension = $dimension;
  }
  /**
   * @return string
   */
  public function getDimension()
  {
    return $this->dimension;
  }
  /**
   * Output only. Description for input parameters
   *
   * @param GoogleCloudDataplexV1DataQualityRuleTemplateParameterDescription[] $inputParameters
   */
  public function setInputParameters($inputParameters)
  {
    $this->inputParameters = $inputParameters;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRuleTemplateParameterDescription[]
   */
  public function getInputParameters()
  {
    return $this->inputParameters;
  }
  /**
   * Output only. The name of the rule template in the format: projects/{project
   * _id_or_number}/locations/{location_id}/entryGroups/{entry_group_id}/entries
   * /{entry_id}
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Output only. Collection of SQLs for data quality rules. Currently only one
   * SQL is supported.
   *
   * @param GoogleCloudDataplexV1DataQualityRuleTemplateSql[] $sqlCollection
   */
  public function setSqlCollection($sqlCollection)
  {
    $this->sqlCollection = $sqlCollection;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRuleTemplateSql[]
   */
  public function getSqlCollection()
  {
    return $this->sqlCollection;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataQualityRuleTemplate::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataQualityRuleTemplate');
