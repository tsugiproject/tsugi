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

class UrlFilteringProfile extends \Google\Collection
{
  protected $collection_key = 'urlFilters';
  protected $urlFiltersType = UrlFilter::class;
  protected $urlFiltersDataType = 'array';

  /**
   * Optional. The list of filtering configs in which each config defines an
   * action to take for some URL match.
   *
   * @param UrlFilter[] $urlFilters
   */
  public function setUrlFilters($urlFilters)
  {
    $this->urlFilters = $urlFilters;
  }
  /**
   * @return UrlFilter[]
   */
  public function getUrlFilters()
  {
    return $this->urlFilters;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(UrlFilteringProfile::class, 'Google_Service_NetworkSecurity_UrlFilteringProfile');
