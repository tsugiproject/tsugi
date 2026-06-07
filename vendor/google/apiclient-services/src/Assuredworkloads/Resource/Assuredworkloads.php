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

namespace Google\Service\Assuredworkloads\Resource;

use Google\Service\Assuredworkloads\GoogleCloudAssuredworkloadsV1ArchiveResourceEventsRequest;
use Google\Service\Assuredworkloads\GoogleCloudAssuredworkloadsV1ArchiveResourceEventsResponse;
use Google\Service\Assuredworkloads\GoogleCloudAssuredworkloadsV1RevertArchivedResourceEventsRequest;
use Google\Service\Assuredworkloads\GoogleCloudAssuredworkloadsV1RevertArchivedResourceEventsResponse;

/**
 * The "assuredworkloads" collection of methods.
 * Typical usage is:
 *  <code>
 *   $assuredworkloadsService = new Google\Service\Assuredworkloads(...);
 *   $assuredworkloads = $assuredworkloadsService->assuredworkloads;
 *  </code>
 */
class Assuredworkloads extends \Google\Service\Resource
{
  /**
   * Finds orphan ResourceEvents matching the criteria and moves them to the
   * ArchivedResourceEvents table. (assuredworkloads.archiveResourceEvents)
   *
   * @param GoogleCloudAssuredworkloadsV1ArchiveResourceEventsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAssuredworkloadsV1ArchiveResourceEventsResponse
   * @throws \Google\Service\Exception
   */
  public function archiveResourceEvents(GoogleCloudAssuredworkloadsV1ArchiveResourceEventsRequest $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('archiveResourceEvents', [$params], GoogleCloudAssuredworkloadsV1ArchiveResourceEventsResponse::class);
  }
  /**
   * Finds matching ArchivedResourceEvents and moves them back to the
   * ResourceEvents table. (assuredworkloads.revertArchivedResourceEvents)
   *
   * @param GoogleCloudAssuredworkloadsV1RevertArchivedResourceEventsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAssuredworkloadsV1RevertArchivedResourceEventsResponse
   * @throws \Google\Service\Exception
   */
  public function revertArchivedResourceEvents(GoogleCloudAssuredworkloadsV1RevertArchivedResourceEventsRequest $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('revertArchivedResourceEvents', [$params], GoogleCloudAssuredworkloadsV1RevertArchivedResourceEventsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Assuredworkloads::class, 'Google_Service_Assuredworkloads_Resource_Assuredworkloads');
