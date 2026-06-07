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

namespace Google\Service\Contactcenterinsights;

class GoogleCloudContactcenterinsightsV1alpha1OutputConfig extends \Google\Model
{
  protected $bigqueryDestinationType = GoogleCloudContactcenterinsightsV1alpha1OutputConfigBigQueryDestination::class;
  protected $bigqueryDestinationDataType = '';
  protected $gcsDestinationType = GoogleCloudContactcenterinsightsV1alpha1OutputConfigGcsDestination::class;
  protected $gcsDestinationDataType = '';
  protected $googleSheetsDestinationType = GoogleCloudContactcenterinsightsV1alpha1OutputConfigGoogleSheetsDestination::class;
  protected $googleSheetsDestinationDataType = '';

  /**
   * Optional. Export to BigQuery.
   *
   * @param GoogleCloudContactcenterinsightsV1alpha1OutputConfigBigQueryDestination $bigqueryDestination
   */
  public function setBigqueryDestination(GoogleCloudContactcenterinsightsV1alpha1OutputConfigBigQueryDestination $bigqueryDestination)
  {
    $this->bigqueryDestination = $bigqueryDestination;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1alpha1OutputConfigBigQueryDestination
   */
  public function getBigqueryDestination()
  {
    return $this->bigqueryDestination;
  }
  /**
   * Optional. Export to a Cloud Storage bucket.
   *
   * @param GoogleCloudContactcenterinsightsV1alpha1OutputConfigGcsDestination $gcsDestination
   */
  public function setGcsDestination(GoogleCloudContactcenterinsightsV1alpha1OutputConfigGcsDestination $gcsDestination)
  {
    $this->gcsDestination = $gcsDestination;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1alpha1OutputConfigGcsDestination
   */
  public function getGcsDestination()
  {
    return $this->gcsDestination;
  }
  /**
   * Optional. Export directly to a Google Sheet.
   *
   * @param GoogleCloudContactcenterinsightsV1alpha1OutputConfigGoogleSheetsDestination $googleSheetsDestination
   */
  public function setGoogleSheetsDestination(GoogleCloudContactcenterinsightsV1alpha1OutputConfigGoogleSheetsDestination $googleSheetsDestination)
  {
    $this->googleSheetsDestination = $googleSheetsDestination;
  }
  /**
   * @return GoogleCloudContactcenterinsightsV1alpha1OutputConfigGoogleSheetsDestination
   */
  public function getGoogleSheetsDestination()
  {
    return $this->googleSheetsDestination;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudContactcenterinsightsV1alpha1OutputConfig::class, 'Google_Service_Contactcenterinsights_GoogleCloudContactcenterinsightsV1alpha1OutputConfig');
