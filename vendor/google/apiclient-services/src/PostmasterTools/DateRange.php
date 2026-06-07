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

namespace Google\Service\PostmasterTools;

class DateRange extends \Google\Model
{
  protected $endType = Date::class;
  protected $endDataType = '';
  protected $startType = Date::class;
  protected $startDataType = '';

  /**
   * Required. The inclusive end date of the date range.
   *
   * @param Date $end
   */
  public function setEnd(Date $end)
  {
    $this->end = $end;
  }
  /**
   * @return Date
   */
  public function getEnd()
  {
    return $this->end;
  }
  /**
   * Required. The inclusive start date of the date range.
   *
   * @param Date $start
   */
  public function setStart(Date $start)
  {
    $this->start = $start;
  }
  /**
   * @return Date
   */
  public function getStart()
  {
    return $this->start;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DateRange::class, 'Google_Service_PostmasterTools_DateRange');
