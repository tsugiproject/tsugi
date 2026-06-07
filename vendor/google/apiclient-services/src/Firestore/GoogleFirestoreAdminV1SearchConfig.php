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

namespace Google\Service\Firestore;

class GoogleFirestoreAdminV1SearchConfig extends \Google\Model
{
  protected $geoSpecType = GoogleFirestoreAdminV1SearchGeoSpec::class;
  protected $geoSpecDataType = '';
  protected $textSpecType = GoogleFirestoreAdminV1SearchTextSpec::class;
  protected $textSpecDataType = '';

  /**
   * Optional. The specification for building a geo search index for a field.
   *
   * @param GoogleFirestoreAdminV1SearchGeoSpec $geoSpec
   */
  public function setGeoSpec(GoogleFirestoreAdminV1SearchGeoSpec $geoSpec)
  {
    $this->geoSpec = $geoSpec;
  }
  /**
   * @return GoogleFirestoreAdminV1SearchGeoSpec
   */
  public function getGeoSpec()
  {
    return $this->geoSpec;
  }
  /**
   * Optional. The specification for building a text search index for a field.
   *
   * @param GoogleFirestoreAdminV1SearchTextSpec $textSpec
   */
  public function setTextSpec(GoogleFirestoreAdminV1SearchTextSpec $textSpec)
  {
    $this->textSpec = $textSpec;
  }
  /**
   * @return GoogleFirestoreAdminV1SearchTextSpec
   */
  public function getTextSpec()
  {
    return $this->textSpec;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirestoreAdminV1SearchConfig::class, 'Google_Service_Firestore_GoogleFirestoreAdminV1SearchConfig');
