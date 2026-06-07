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

class GoogleCloudDataplexV1DataProfileSpec extends \Google\Model
{
  /**
   * Default value. This value is unused.
   */
  public const MODE_MODE_UNSPECIFIED = 'MODE_UNSPECIFIED';
  /**
   * Performs standard profiling. The behavior is controlled by other fields
   * such as sampling_percent, row_filter, and column filters. This mode allows
   * for full scans or custom sampling.
   */
  public const MODE_STANDARD = 'STANDARD';
  /**
   * Specifies lightweight profiling mode. This mode is optimized for low-
   * latency, low-fidelity profiling.When this mode is selected, the following
   * fields must not be set: sampling_percent, row_filter, include_fields, and
   * exclude_fields.
   */
  public const MODE_LIGHTWEIGHT = 'LIGHTWEIGHT';
  /**
   * Optional. If set, the latest DataScan job result will be published as
   * Dataplex Universal Catalog metadata.
   *
   * @var bool
   */
  public $catalogPublishingEnabled;
  protected $excludeFieldsType = GoogleCloudDataplexV1DataProfileSpecSelectedFields::class;
  protected $excludeFieldsDataType = '';
  protected $includeFieldsType = GoogleCloudDataplexV1DataProfileSpecSelectedFields::class;
  protected $includeFieldsDataType = '';
  /**
   * Optional. The execution mode for the profile scan.
   *
   * @var string
   */
  public $mode;
  protected $postScanActionsType = GoogleCloudDataplexV1DataProfileSpecPostScanActions::class;
  protected $postScanActionsDataType = '';
  /**
   * Optional. A filter applied to all rows in a single DataScan job. The filter
   * needs to be a valid SQL expression for a WHERE clause in BigQuery standard
   * SQL syntax. Example: col1 >= 0 AND col2 < 10
   *
   * @var string
   */
  public $rowFilter;
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
   * Optional. The fields to exclude from data profile.If specified, the fields
   * will be excluded from data profile, regardless of include_fields value.
   *
   * @param GoogleCloudDataplexV1DataProfileSpecSelectedFields $excludeFields
   */
  public function setExcludeFields(GoogleCloudDataplexV1DataProfileSpecSelectedFields $excludeFields)
  {
    $this->excludeFields = $excludeFields;
  }
  /**
   * @return GoogleCloudDataplexV1DataProfileSpecSelectedFields
   */
  public function getExcludeFields()
  {
    return $this->excludeFields;
  }
  /**
   * Optional. The fields to include in data profile.If not specified, all
   * fields at the time of profile scan job execution are included, except for
   * ones listed in exclude_fields.
   *
   * @param GoogleCloudDataplexV1DataProfileSpecSelectedFields $includeFields
   */
  public function setIncludeFields(GoogleCloudDataplexV1DataProfileSpecSelectedFields $includeFields)
  {
    $this->includeFields = $includeFields;
  }
  /**
   * @return GoogleCloudDataplexV1DataProfileSpecSelectedFields
   */
  public function getIncludeFields()
  {
    return $this->includeFields;
  }
  /**
   * Optional. The execution mode for the profile scan.
   *
   * Accepted values: MODE_UNSPECIFIED, STANDARD, LIGHTWEIGHT
   *
   * @param self::MODE_* $mode
   */
  public function setMode($mode)
  {
    $this->mode = $mode;
  }
  /**
   * @return self::MODE_*
   */
  public function getMode()
  {
    return $this->mode;
  }
  /**
   * Optional. Actions to take upon job completion..
   *
   * @param GoogleCloudDataplexV1DataProfileSpecPostScanActions $postScanActions
   */
  public function setPostScanActions(GoogleCloudDataplexV1DataProfileSpecPostScanActions $postScanActions)
  {
    $this->postScanActions = $postScanActions;
  }
  /**
   * @return GoogleCloudDataplexV1DataProfileSpecPostScanActions
   */
  public function getPostScanActions()
  {
    return $this->postScanActions;
  }
  /**
   * Optional. A filter applied to all rows in a single DataScan job. The filter
   * needs to be a valid SQL expression for a WHERE clause in BigQuery standard
   * SQL syntax. Example: col1 >= 0 AND col2 < 10
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
class_alias(GoogleCloudDataplexV1DataProfileSpec::class, 'Google_Service_CloudDataplex_GoogleCloudDataplexV1DataProfileSpec');
