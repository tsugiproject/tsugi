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

namespace Google\Service\Dfareporting;

class CompatibleFields extends \Google\Model
{
  protected $crossDimensionReachReportCompatibleFieldsType = CrossDimensionReachReportCompatibleFields::class;
  protected $crossDimensionReachReportCompatibleFieldsDataType = '';
  protected $crossMediaReachReportCompatibleFieldsType = CrossMediaReachReportCompatibleFields::class;
  protected $crossMediaReachReportCompatibleFieldsDataType = '';
  protected $floodlightReportCompatibleFieldsType = FloodlightReportCompatibleFields::class;
  protected $floodlightReportCompatibleFieldsDataType = '';
  /**
   * @var string
   */
  public $kind;
  protected $pathToConversionReportCompatibleFieldsType = PathToConversionReportCompatibleFields::class;
  protected $pathToConversionReportCompatibleFieldsDataType = '';
  protected $reachReportCompatibleFieldsType = ReachReportCompatibleFields::class;
  protected $reachReportCompatibleFieldsDataType = '';
  protected $reportCompatibleFieldsType = ReportCompatibleFields::class;
  protected $reportCompatibleFieldsDataType = '';

  /**
   * @param CrossDimensionReachReportCompatibleFields
   */
  public function setCrossDimensionReachReportCompatibleFields(CrossDimensionReachReportCompatibleFields $crossDimensionReachReportCompatibleFields)
  {
    $this->crossDimensionReachReportCompatibleFields = $crossDimensionReachReportCompatibleFields;
  }
  /**
   * @return CrossDimensionReachReportCompatibleFields
   */
  public function getCrossDimensionReachReportCompatibleFields()
  {
    return $this->crossDimensionReachReportCompatibleFields;
  }
  /**
   * @param CrossMediaReachReportCompatibleFields
   */
  public function setCrossMediaReachReportCompatibleFields(CrossMediaReachReportCompatibleFields $crossMediaReachReportCompatibleFields)
  {
    $this->crossMediaReachReportCompatibleFields = $crossMediaReachReportCompatibleFields;
  }
  /**
   * @return CrossMediaReachReportCompatibleFields
   */
  public function getCrossMediaReachReportCompatibleFields()
  {
    return $this->crossMediaReachReportCompatibleFields;
  }
  /**
   * @param FloodlightReportCompatibleFields
   */
  public function setFloodlightReportCompatibleFields(FloodlightReportCompatibleFields $floodlightReportCompatibleFields)
  {
    $this->floodlightReportCompatibleFields = $floodlightReportCompatibleFields;
  }
  /**
   * @return FloodlightReportCompatibleFields
   */
  public function getFloodlightReportCompatibleFields()
  {
    return $this->floodlightReportCompatibleFields;
  }
  /**
   * @param string
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * @param PathToConversionReportCompatibleFields
   */
  public function setPathToConversionReportCompatibleFields(PathToConversionReportCompatibleFields $pathToConversionReportCompatibleFields)
  {
    $this->pathToConversionReportCompatibleFields = $pathToConversionReportCompatibleFields;
  }
  /**
   * @return PathToConversionReportCompatibleFields
   */
  public function getPathToConversionReportCompatibleFields()
  {
    return $this->pathToConversionReportCompatibleFields;
  }
  /**
   * @param ReachReportCompatibleFields
   */
  public function setReachReportCompatibleFields(ReachReportCompatibleFields $reachReportCompatibleFields)
  {
    $this->reachReportCompatibleFields = $reachReportCompatibleFields;
  }
  /**
   * @return ReachReportCompatibleFields
   */
  public function getReachReportCompatibleFields()
  {
    return $this->reachReportCompatibleFields;
  }
  /**
   * @param ReportCompatibleFields
   */
  public function setReportCompatibleFields(ReportCompatibleFields $reportCompatibleFields)
  {
    $this->reportCompatibleFields = $reportCompatibleFields;
  }
  /**
   * @return ReportCompatibleFields
   */
  public function getReportCompatibleFields()
  {
    return $this->reportCompatibleFields;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CompatibleFields::class, 'Google_Service_Dfareporting_CompatibleFields');
