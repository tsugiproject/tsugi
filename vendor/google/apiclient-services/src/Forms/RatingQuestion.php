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

namespace Google\Service\Forms;

class RatingQuestion extends \Google\Model
{
  /**
   * @var string
   */
  public $iconType;
  /**
   * @var int
   */
  public $ratingScaleLevel;

  /**
   * @param string
   */
  public function setIconType($iconType)
  {
    $this->iconType = $iconType;
  }
  /**
   * @return string
   */
  public function getIconType()
  {
    return $this->iconType;
  }
  /**
   * @param int
   */
  public function setRatingScaleLevel($ratingScaleLevel)
  {
    $this->ratingScaleLevel = $ratingScaleLevel;
  }
  /**
   * @return int
   */
  public function getRatingScaleLevel()
  {
    return $this->ratingScaleLevel;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RatingQuestion::class, 'Google_Service_Forms_RatingQuestion');
