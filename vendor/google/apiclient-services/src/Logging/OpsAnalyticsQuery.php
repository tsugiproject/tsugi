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

class OpsAnalyticsQuery extends \Google\Model
{
  protected $queryBuilderType = QueryBuilderConfig::class;
  protected $queryBuilderDataType = '';
  /**
   * Optional. A Log Analytics SQL query in text format.If both sql_query_text
   * and query_builder fields are set, then the sql_query_text will be used, if
   * its non-empty. At least one of the two fields must be set.
   *
   * @var string
   */
  public $sqlQueryText;

  /**
   * Optional. A query builder configuration used in Log Analytics.If both
   * query_builder and sql_query_text fields are set, then the sql_query_text
   * will be used, if its non-empty. At least one of the two fields must be set.
   *
   * @param QueryBuilderConfig $queryBuilder
   */
  public function setQueryBuilder(QueryBuilderConfig $queryBuilder)
  {
    $this->queryBuilder = $queryBuilder;
  }
  /**
   * @return QueryBuilderConfig
   */
  public function getQueryBuilder()
  {
    return $this->queryBuilder;
  }
  /**
   * Optional. A Log Analytics SQL query in text format.If both sql_query_text
   * and query_builder fields are set, then the sql_query_text will be used, if
   * its non-empty. At least one of the two fields must be set.
   *
   * @param string $sqlQueryText
   */
  public function setSqlQueryText($sqlQueryText)
  {
    $this->sqlQueryText = $sqlQueryText;
  }
  /**
   * @return string
   */
  public function getSqlQueryText()
  {
    return $this->sqlQueryText;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(OpsAnalyticsQuery::class, 'Google_Service_Logging_OpsAnalyticsQuery');
