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

namespace Google\Service\NetworkSecurity;

class UrlFilter extends \Google\Collection
{
  /**
   * Filtering action not specified.
   */
  public const FILTERING_ACTION_URL_FILTERING_ACTION_UNSPECIFIED = 'URL_FILTERING_ACTION_UNSPECIFIED';
  /**
   * The connection matching this filter will be allowed to transmit.
   */
  public const FILTERING_ACTION_ALLOW = 'ALLOW';
  /**
   * The connection matching this filter will be dropped.
   */
  public const FILTERING_ACTION_DENY = 'DENY';
  protected $collection_key = 'urls';
  /**
   * Required. The action taken when this filter is applied.
   *
   * @var string
   */
  public $filteringAction;
  /**
   * Required. The priority of this filter within the URL Filtering Profile.
   * Lower integers indicate higher priorities. The priority of a filter must be
   * unique within a URL Filtering Profile.
   *
   * @var int
   */
  public $priority;
  /**
   * Required. The list of strings that a URL must match with for this filter to
   * be applied.
   *
   * @var string[]
   */
  public $urls;

  /**
   * Required. The action taken when this filter is applied.
   *
   * Accepted values: URL_FILTERING_ACTION_UNSPECIFIED, ALLOW, DENY
   *
   * @param self::FILTERING_ACTION_* $filteringAction
   */
  public function setFilteringAction($filteringAction)
  {
    $this->filteringAction = $filteringAction;
  }
  /**
   * @return self::FILTERING_ACTION_*
   */
  public function getFilteringAction()
  {
    return $this->filteringAction;
  }
  /**
   * Required. The priority of this filter within the URL Filtering Profile.
   * Lower integers indicate higher priorities. The priority of a filter must be
   * unique within a URL Filtering Profile.
   *
   * @param int $priority
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  /**
   * @return int
   */
  public function getPriority()
  {
    return $this->priority;
  }
  /**
   * Required. The list of strings that a URL must match with for this filter to
   * be applied.
   *
   * @param string[] $urls
   */
  public function setUrls($urls)
  {
    $this->urls = $urls;
  }
  /**
   * @return string[]
   */
  public function getUrls()
  {
    return $this->urls;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(UrlFilter::class, 'Google_Service_NetworkSecurity_UrlFilter');
