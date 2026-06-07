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

namespace Google\Service\WorkspaceEvents;

class DriveOptions extends \Google\Model
{
  /**
   * Optional. Immutable. For subscriptions to Google Drive events, whether to
   * receive events about Drive files that are children of the target folder or
   * shared drive. * If `false`, the subscription only receives events about
   * changes to the folder or shared drive that's specified as the
   * `targetResource`. * If `true`, the `mimeType` field of the `file` resource
   * must be set to `application/vnd.google-apps.folder`. For details, see
   * [Google Drive event
   * types](https://developers.google.com/workspace/events/guides/events-
   * drive#event-types).
   *
   * @var bool
   */
  public $includeDescendants;

  /**
   * Optional. Immutable. For subscriptions to Google Drive events, whether to
   * receive events about Drive files that are children of the target folder or
   * shared drive. * If `false`, the subscription only receives events about
   * changes to the folder or shared drive that's specified as the
   * `targetResource`. * If `true`, the `mimeType` field of the `file` resource
   * must be set to `application/vnd.google-apps.folder`. For details, see
   * [Google Drive event
   * types](https://developers.google.com/workspace/events/guides/events-
   * drive#event-types).
   *
   * @param bool $includeDescendants
   */
  public function setIncludeDescendants($includeDescendants)
  {
    $this->includeDescendants = $includeDescendants;
  }
  /**
   * @return bool
   */
  public function getIncludeDescendants()
  {
    return $this->includeDescendants;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DriveOptions::class, 'Google_Service_WorkspaceEvents_DriveOptions');
