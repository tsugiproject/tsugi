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

class GoogleCloudDataplexV1DataQualityRuleTemplateReference extends \Google\Model
{
  /**
   * Required. The template entry name. Entry must be of EntryType
   * projects/dataplex-types/locations/global/entryTypes/data-quality-rule-
   * template and contains top-level aspect of AspectType projects/dataplex-
   * types/locations/global/aspectTypes/data-quality-rule-template. The format
   * is: projects/{project_id_or_number}/locations/{location_id}/entryGroups/{en
   * try_group_id}/entries/{entry_id}
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The resolved SQL statement generated from the template with
   * parameters substituted. It is only populated in the result.
   *
   * @var string
   */
  public $resolvedSql;
  protected $ruleTemplateType = GoogleCloudDataplexV1DataQualityRuleTemplate::class;
  protected $ruleTemplateDataType = '';
  protected $valuesType = GoogleCloudDataplexV1DataQualityRuleTemplateReferenceParameterValue::class;
  protected $valuesDataType = 'map';

  /**
   * Required. The template entry name. Entry must be of EntryType
   * projects/dataplex-types/locations/global/entryTypes/data-quality-rule-
   * template and contains top-level aspect of AspectType projects/dataplex-
   * types/locations/global/aspectTypes/data-quality-rule-template. The format
   * is: projects/{project_id_or_number}/locations/{location_id}/entryGroups/{en
   * try_group_id}/entries/{entry_id}
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
   * Output only. The resolved SQL statement generated from the template with
   * parameters substituted. It is only populated in the result.
   *
   * @param string $resolvedSql
   */
  public function setResolvedSql($resolvedSql)
  {
    $this->resolvedSql = $resolvedSql;
  }
  /**
   * @return string
   */
  public function getResolvedSql()
  {
    return $this->resolvedSql;
  }
  /**
   * Output only. The rule template used to resolve the rule. It is only
   * populated in the result.
   *
   * @param GoogleCloudDataplexV1DataQualityRuleTemplate $ruleTemplate
   */
  public function setRuleTemplate(GoogleCloudDataplexV1DataQualityRuleTemplate $ruleTemplate)
  {
    $this->ruleTemplate = $ruleTemplate;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRuleTemplate
   */
  public function getRuleTemplate()
  {
    return $this->ruleTemplate;
  }
  /**
   * Optional. Provides the map of parameter name and value. The maximum size of
   * the field is 120KB (encoded as UTF-8).
   *
   * @param GoogleCloudDataplexV1DataQualityRuleTemplateReferenceParameterValue[] $values
   */
  public function setValues($values)
  {
    $this->values = $values;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRuleTemplateReferenceParameterValue[]
   */
  public function getValues()
  {
    return $this->values;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataQualityRuleTemplateReference::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataQualityRuleTemplateReference');
