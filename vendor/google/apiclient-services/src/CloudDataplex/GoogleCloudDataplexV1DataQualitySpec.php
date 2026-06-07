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

class GoogleCloudDataplexV1DataQualitySpec extends \Google\Collection
{
  protected $collection_key = 'rules';
  /**
   * Optional. If set, the latest DataScan job result will be published as
   * Dataplex Universal Catalog metadata.
   *
   * @var bool
   */
  public $catalogPublishingEnabled;
  /**
   * Optional. If enabled, the data scan will retrieve rules defined in the
   * dataplex-types.global.data-rules aspect on all paths of the catalog entry
   * corresponding to the BigQuery table resource and all attached glossary
   * terms. The path that data-rules aspect is attached on the table entry
   * defines the column that the rule will be evaluated against. For glossary
   * terms, the path that the terms are attached on the table entry defines the
   * column that the rule will be evaluated against. At the start of scan
   * execution, the rules reflect the latest state retrieved from the catalog
   * entry and any updates on the rules thereafter are ignored for that
   * execution. The updates will be reflected from the next execution. Rules
   * defined in the datascan must be empty if this field is enabled.
   *
   * @var bool
   */
  public $enableCatalogBasedRules;
  /**
   * Optional. Filter for selectively running a subset of rules. You can filter
   * the request by the name or attribute key-value pairs defined on the rule.
   * If not specified, all rules are run. The filter is applicable to both, the
   * rules retrieved from catalog and explicitly defined rules in the scan.
   * Please see filter syntax (https://docs.cloud.google.com/dataplex/docs/auto-
   * data-quality-overview#rule-filtering) for more details.
   *
   * @var string
   */
  public $filter;
  protected $postScanActionsType = GoogleCloudDataplexV1DataQualitySpecPostScanActions::class;
  protected $postScanActionsDataType = '';
  /**
   * Optional. A filter applied to all rows in a single DataScan job. The filter
   * needs to be a valid SQL expression for a WHERE clause in GoogleSQL syntax
   * (https://cloud.google.com/bigquery/docs/reference/standard-sql/query-
   * syntax#where_clause).Example: col1 >= 0 AND col2 < 10
   *
   * @var string
   */
  public $rowFilter;
  protected $rulesType = GoogleCloudDataplexV1DataQualityRule::class;
  protected $rulesDataType = 'array';
  /**
   * Optional. The percentage of the records to be selected from the dataset for
   * DataScan. Value can range between 0.0 and 100.0 with up to 3 significant
   * decimal digits. Sampling is not applied if sampling_percent is not
   * specified, 0 or 100.
   *
   * @var float
   */
  public $samplingPercent;

  /**
   * Optional. If set, the latest DataScan job result will be published as
   * Dataplex Universal Catalog metadata.
   *
   * @param bool $catalogPublishingEnabled
   */
  public function setCatalogPublishingEnabled($catalogPublishingEnabled)
  {
    $this->catalogPublishingEnabled = $catalogPublishingEnabled;
  }
  /**
   * @return bool
   */
  public function getCatalogPublishingEnabled()
  {
    return $this->catalogPublishingEnabled;
  }
  /**
   * Optional. If enabled, the data scan will retrieve rules defined in the
   * dataplex-types.global.data-rules aspect on all paths of the catalog entry
   * corresponding to the BigQuery table resource and all attached glossary
   * terms. The path that data-rules aspect is attached on the table entry
   * defines the column that the rule will be evaluated against. For glossary
   * terms, the path that the terms are attached on the table entry defines the
   * column that the rule will be evaluated against. At the start of scan
   * execution, the rules reflect the latest state retrieved from the catalog
   * entry and any updates on the rules thereafter are ignored for that
   * execution. The updates will be reflected from the next execution. Rules
   * defined in the datascan must be empty if this field is enabled.
   *
   * @param bool $enableCatalogBasedRules
   */
  public function setEnableCatalogBasedRules($enableCatalogBasedRules)
  {
    $this->enableCatalogBasedRules = $enableCatalogBasedRules;
  }
  /**
   * @return bool
   */
  public function getEnableCatalogBasedRules()
  {
    return $this->enableCatalogBasedRules;
  }
  /**
   * Optional. Filter for selectively running a subset of rules. You can filter
   * the request by the name or attribute key-value pairs defined on the rule.
   * If not specified, all rules are run. The filter is applicable to both, the
   * rules retrieved from catalog and explicitly defined rules in the scan.
   * Please see filter syntax (https://docs.cloud.google.com/dataplex/docs/auto-
   * data-quality-overview#rule-filtering) for more details.
   *
   * @param string $filter
   */
  public function setFilter($filter)
  {
    $this->filter = $filter;
  }
  /**
   * @return string
   */
  public function getFilter()
  {
    return $this->filter;
  }
  /**
   * Optional. Actions to take upon job completion.
   *
   * @param GoogleCloudDataplexV1DataQualitySpecPostScanActions $postScanActions
   */
  public function setPostScanActions(GoogleCloudDataplexV1DataQualitySpecPostScanActions $postScanActions)
  {
    $this->postScanActions = $postScanActions;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualitySpecPostScanActions
   */
  public function getPostScanActions()
  {
    return $this->postScanActions;
  }
  /**
   * Optional. A filter applied to all rows in a single DataScan job. The filter
   * needs to be a valid SQL expression for a WHERE clause in GoogleSQL syntax
   * (https://cloud.google.com/bigquery/docs/reference/standard-sql/query-
   * syntax#where_clause).Example: col1 >= 0 AND col2 < 10
   *
   * @param string $rowFilter
   */
  public function setRowFilter($rowFilter)
  {
    $this->rowFilter = $rowFilter;
  }
  /**
   * @return string
   */
  public function getRowFilter()
  {
    return $this->rowFilter;
  }
  /**
   * Required. The list of rules to evaluate against a data source. At least one
   * rule is required.
   *
   * @param GoogleCloudDataplexV1DataQualityRule[] $rules
   */
  public function setRules($rules)
  {
    $this->rules = $rules;
  }
  /**
   * @return GoogleCloudDataplexV1DataQualityRule[]
   */
  public function getRules()
  {
    return $this->rules;
  }
  /**
   * Optional. The percentage of the records to be selected from the dataset for
   * DataScan. Value can range between 0.0 and 100.0 with up to 3 significant
   * decimal digits. Sampling is not applied if sampling_percent is not
   * specified, 0 or 100.
   *
   * @param float $samplingPercent
   */
  public function setSamplingPercent($samplingPercent)
  {
    $this->samplingPercent = $samplingPercent;
  }
  /**
   * @return float
   */
  public function getSamplingPercent()
  {
    return $this->samplingPercent;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDataplexV1DataQualitySpec::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataQualitySpec');
