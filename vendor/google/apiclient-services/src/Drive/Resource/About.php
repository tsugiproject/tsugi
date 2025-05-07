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

namespace Google\Service\Drive\Resource;

use Google\Service\Drive\About as AboutModel;

/**
 * The "about" collection of methods.
 * Typical usage is:
 *  <code>
 *   $driveService = new Google\Service\Drive(...);
 *   $about = $driveService->about;
 *  </code>
 */
class About extends \Google\Service\Resource
{
  /**
   * Gets information about the user, the user's Drive, and system capabilities.
   * For more information, see [Return user
   * info](https://developers.google.com/drive/api/guides/user-info). Required:
   * The `fields` parameter must be set. To return the exact fields you need, see
   * [Return specific
   * fields](https://developers.google.com/drive/api/guides/fields-parameter).
   * (about.get)
   *
   * @param array $optParams Optional parameters.
   * @return AboutModel
   * @throws \Google\Service\Exception
   */
  public function get($optParams = [])
  {
    $params = [];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], AboutModel::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(About::class, 'Google_Service_Drive_Resource_About');
