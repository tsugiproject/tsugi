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

class QueryBuilderConfig extends \Google\Collection
{
  protected $collection_key = 'resourceNames';
  protected $fieldSourcesType = FieldSource::class;
  protected $fieldSourcesDataType = 'array';
  protected $filterType = FilterPredicate::class;
  protected $filterDataType = '';
  /**
   * The limit to use for the query. This equates to the LIMIT clause in SQL. A
   * limit of 0 will be treated as not enabled.
   *
   * @var string
   */
  public $limit;
  protected $orderBysType = SortOrderParameter::class;
  protected $orderBysDataType = 'array';
  /**
   * Required. The view/resource to query. For now only a single view/resource
   * will be sent, but there are plans to allow multiple views in the future.
   * Marking as repeated for that purpose. Example: -
   * "projects/123/locations/global/buckets/456/views/_Default" -
   * "projects/123/locations/global/metricBuckets/456/views/_Default"
   *
   * @var string[]
   */
  public $resourceNames;
  /**
   * The plain text search to use for the query. There is no support for
   * multiple search terms. This uses the SEARCH functionality in BigQuery. For
   * example, a search_term = 'ERROR' would result in the following SQL:SELECT *
   * FROM resource WHERE SEARCH(resource, 'ERROR') LIMIT 100
   *
   * @var string
   */
  public $searchTerm;

  /**
   * Defines the items to include in the query result, analogous to a SQL SELECT
   * clause.
   *
   * @param FieldSource[] $fieldSources
   */
  public function setFieldSources($fieldSources)
  {
    $this->fieldSources = $fieldSources;
  }
  /**
   * @return FieldSource[]
   */
  public function getFieldSources()
  {
    return $this->fieldSources;
  }
  /**
   * The filter to use for the query. This equates to the WHERE clause in SQL.
   *
   * @param FilterPredicate $filter
   */
  public function setFilter(FilterPredicate $filter)
  {
    $this->filter = $filter;
  }
  /**
   * @return FilterPredicate
   */
  public function getFilter()
  {
    return $this->filter;
  }
  /**
   * The limit to use for the query. This equates to the LIMIT clause in SQL. A
   * limit of 0 will be treated as not enabled.
   *
   * @param string $limit
   */
  public function setLimit($limit)
  {
    $this->limit = $limit;
  }
  /**
   * @return string
   */
  public function getLimit()
  {
    return $this->limit;
  }
  /**
   * The sort orders to use for the query. This equates to the ORDER BY clause
   * in SQL.
   *
   * @param SortOrderParameter[] $orderBys
   */
  public function setOrderBys($orderBys)
  {
    $this->orderBys = $orderBys;
  }
  /**
   * @return SortOrderParameter[]
   */
  public function getOrderBys()
  {
    return $this->orderBys;
  }
  /**
   * Required. The view/resource to query. For now only a single view/resource
   * will be sent, but there are plans to allow multiple views in the future.
   * Marking as repeated for that purpose. Example: -
   * "projects/123/locations/global/buckets/456/views/_Default" -
   * "projects/123/locations/global/metricBuckets/456/views/_Default"
   *
   * @param string[] $resourceNames
   */
  public function setResourceNames($resourceNames)
  {
    $this->resourceNames = $resourceNames;
  }
  /**
   * @return string[]
   */
  public function getResourceNames()
  {
    return $this->resourceNames;
  }
  /**
   * The plain text search to use for the query. There is no support for
   * multiple search terms. This uses the SEARCH functionality in BigQuery. For
   * example, a search_term = 'ERROR' would result in the following SQL:SELECT *
   * FROM resource WHERE SEARCH(resource, 'ERROR') LIMIT 100
   *
   * @param string $searchTerm
   */
  public function setSearchTerm($searchTerm)
  {
    $this->searchTerm = $searchTerm;
  }
  /**
   * @return string
   */
  public function getSearchTerm()
  {
    return $this->searchTerm;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(QueryBuilderConfig::class, 'Google_Service_Logging_QueryBuilderConfig');
