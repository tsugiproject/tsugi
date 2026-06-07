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

namespace Google\Service\GoogleHealthAPI;

class SwimLengthsData extends \Google\Model
{
  /**
   * Swim stroke type is unspecified.
   */
  public const SWIM_STROKE_TYPE_SWIM_STROKE_TYPE_UNSPECIFIED = 'SWIM_STROKE_TYPE_UNSPECIFIED';
  /**
   * Freestyle swim stroke type.
   */
  public const SWIM_STROKE_TYPE_FREESTYLE = 'FREESTYLE';
  /**
   * Backstroke swim stroke type.
   */
  public const SWIM_STROKE_TYPE_BACKSTROKE = 'BACKSTROKE';
  /**
   * Breaststroke swim stroke type.
   */
  public const SWIM_STROKE_TYPE_BREASTSTROKE = 'BREASTSTROKE';
  /**
   * Butterfly swim stroke type.
   */
  public const SWIM_STROKE_TYPE_BUTTERFLY = 'BUTTERFLY';
  protected $intervalType = ObservationTimeInterval::class;
  protected $intervalDataType = '';
  /**
   * Required. Number of strokes in the lap.
   *
   * @var string
   */
  public $strokeCount;
  /**
   * Required. Swim stroke type.
   *
   * @var string
   */
  public $swimStrokeType;

  /**
   * Required. Observed interval.
   *
   * @param ObservationTimeInterval $interval
   */
  public function setInterval(ObservationTimeInterval $interval)
  {
    $this->interval = $interval;
  }
  /**
   * @return ObservationTimeInterval
   */
  public function getInterval()
  {
    return $this->interval;
  }
  /**
   * Required. Number of strokes in the lap.
   *
   * @param string $strokeCount
   */
  public function setStrokeCount($strokeCount)
  {
    $this->strokeCount = $strokeCount;
  }
  /**
   * @return string
   */
  public function getStrokeCount()
  {
    return $this->strokeCount;
  }
  /**
   * Required. Swim stroke type.
   *
   * Accepted values: SWIM_STROKE_TYPE_UNSPECIFIED, FREESTYLE, BACKSTROKE,
   * BREASTSTROKE, BUTTERFLY
   *
   * @param self::SWIM_STROKE_TYPE_* $swimStrokeType
   */
  public function setSwimStrokeType($swimStrokeType)
  {
    $this->swimStrokeType = $swimStrokeType;
  }
  /**
   * @return self::SWIM_STROKE_TYPE_*
   */
  public function getSwimStrokeType()
  {
    return $this->swimStrokeType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SwimLengthsData::class, 'Google_Service_GoogleHealthAPI_SwimLengthsData');
