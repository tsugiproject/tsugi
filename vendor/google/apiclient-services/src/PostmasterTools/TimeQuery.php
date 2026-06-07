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

class TimeQuery extends \Google\Model
{
  protected $dateListType = DateList::class;
  protected $dateListDataType = '';
  protected $dateRangesType = DateRanges::class;
  protected $dateRangesDataType = '';

  /**
   * A list of specific dates.
   *
   * @param DateList $dateList
   */
  public function setDateList(DateList $dateList)
  {
    $this->dateList = $dateList;
  }
  /**
   * @return DateList
   */
  public function getDateList()
  {
    return $this->dateList;
  }
  /**
   * A list of date ranges.
   *
   * @param DateRanges $dateRanges
   */
  public function setDateRanges(DateRanges $dateRanges)
  {
    $this->dateRanges = $dateRanges;
  }
  /**
   * @return DateRanges
   */
  public function getDateRanges()
  {
    return $this->dateRanges;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TimeQuery::class, 'Google_Service_PostmasterTools_TimeQuery');
