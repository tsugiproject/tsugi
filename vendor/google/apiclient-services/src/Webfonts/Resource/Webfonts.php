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

namespace Google\Service\Webfonts\Resource;

use Google\Service\Webfonts\WebfontList;

/**
 * The "webfonts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $webfontsService = new Google\Service\Webfonts(...);
 *   $webfonts = $webfontsService->webfonts;
 *  </code>
 */
class Webfonts extends \Google\Service\Resource
{
  /**
   * Retrieves the list of fonts currently served by the Google Fonts Developer
   * API. (webfonts.listWebfonts)
   *
   * @param array $optParams Optional parameters.
   *
   * @opt_param string capability Controls the font urls in `Webfont.files`, by
   * default, static ttf fonts are sent.
   * @opt_param string category Filters by Webfont.category, if category is found
   * in Webfont.categories. If not set, returns all families.
   * @opt_param string family Filters by Webfont.family, using literal match. If
   * not set, returns all families
   * @opt_param string sort Enables sorting of the list.
   * @opt_param string subset Filters by Webfont.subset, if subset is found in
   * Webfont.subsets. If not set, returns all families.
   * @return WebfontList
   * @throws \Google\Service\Exception
   */
  public function listWebfonts($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], WebfontList::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Webfonts::class, 'Google_Service_Webfonts_Resource_Webfonts');
