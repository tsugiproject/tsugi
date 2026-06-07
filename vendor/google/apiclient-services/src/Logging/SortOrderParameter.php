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

namespace Google\Service\Logging;

class SortOrderParameter extends \Google\Model
{
  /**
   * Invalid value, do not use.
   */
  public const SORT_ORDER_DIRECTION_SORT_ORDER_UNSPECIFIED = 'SORT_ORDER_UNSPECIFIED';
  /**
   * No sorting will be applied. This is used to determine if the query is in
   * pass thru mode. To correctly chart a query in pass thru mode, NONE will
   * need to be sent
   */
  public const SORT_ORDER_DIRECTION_SORT_ORDER_NONE = 'SORT_ORDER_NONE';
  /**
   * The lowest-valued entries will be selected.
   */
  public const SORT_ORDER_DIRECTION_SORT_ORDER_ASCENDING = 'SORT_ORDER_ASCENDING';
  /**
   * The highest-valued entries will be selected.
   */
  public const SORT_ORDER_DIRECTION_SORT_ORDER_DESCENDING = 'SORT_ORDER_DESCENDING';
  protected $fieldSourceType = FieldSource::class;
  protected $fieldSourceDataType = '';
  /**
   * The sort order to use for the query.
   *
   * @var string
   */
  public $sortOrderDirection;

  /**
   * The field to sort on. Can be one of the FieldSource types: field name,
   * alias ref, variable ref, or a literal value.
   *
   * @param FieldSource $fieldSource
   */
  public function setFieldSource(FieldSource $fieldSource)
  {
    $this->fieldSource = $fieldSource;
  }
  /**
   * @return FieldSource
   */
  public function getFieldSource()
  {
    return $this->fieldSource;
  }
  /**
   * The sort order to use for the query.
   *
   * Accepted values: SORT_ORDER_UNSPECIFIED, SORT_ORDER_NONE,
   * SORT_ORDER_ASCENDING, SORT_ORDER_DESCENDING
   *
   * @param self::SORT_ORDER_DIRECTION_* $sortOrderDirection
   */
  public function setSortOrderDirection($sortOrderDirection)
  {
    $this->sortOrderDirection = $sortOrderDirection;
  }
  /**
   * @return self::SORT_ORDER_DIRECTION_*
   */
  public function getSortOrderDirection()
  {
    return $this->sortOrderDirection;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SortOrderParameter::class, 'Google_Service_Logging_SortOrderParameter');
