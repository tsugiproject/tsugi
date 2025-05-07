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

namespace Google\Service\ArtifactRegistry\Resource;

use Google\Service\ArtifactRegistry\Attachment;
use Google\Service\ArtifactRegistry\ListAttachmentsResponse;
use Google\Service\ArtifactRegistry\Operation;

/**
 * The "attachments" collection of methods.
 * Typical usage is:
 *  <code>
 *   $artifactregistryService = new Google\Service\ArtifactRegistry(...);
 *   $attachments = $artifactregistryService->projects_locations_repositories_attachments;
 *  </code>
 */
class ProjectsLocationsRepositoriesAttachments extends \Google\Service\Resource
{
  /**
   * Creates an attachment. The returned Operation will finish once the attachment
   * has been created. Its response will be the created attachment.
   * (attachments.create)
   *
   * @param string $parent Required. The name of the parent resource where the
   * attachment will be created.
   * @param Attachment $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string attachmentId Required. The attachment id to use for this
   * attachment.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function create($parent, Attachment $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], Operation::class);
  }
  /**
   * Deletes an attachment. The returned Operation will finish once the
   * attachments has been deleted. It will not have any Operation metadata and
   * will return a `google.protobuf.Empty` response. (attachments.delete)
   *
   * @param string $name Required. The name of the attachment to delete.
   * @param array $optParams Optional parameters.
   * @return Operation
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], Operation::class);
  }
  /**
   * Gets an attachment. (attachments.get)
   *
   * @param string $name Required. The name of the attachment to retrieve.
   * @param array $optParams Optional parameters.
   * @return Attachment
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Attachment::class);
  }
  /**
   * Lists attachments. (attachments.listProjectsLocationsRepositoriesAttachments)
   *
   * @param string $parent Required. The name of the parent resource whose
   * attachments will be listed.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string filter Optional. An expression for filtering the results of
   * the request. Filter rules are case insensitive. The fields eligible for
   * filtering are: * `target` * `type` * `attachment_namespace`
   * @opt_param int pageSize The maximum number of attachments to return. Maximum
   * page size is 1,000.
   * @opt_param string pageToken The next_page_token value returned from a
   * previous list request, if any.
   * @return ListAttachmentsResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsRepositoriesAttachments($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], ListAttachmentsResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsRepositoriesAttachments::class, 'Google_Service_ArtifactRegistry_Resource_ProjectsLocationsRepositoriesAttachments');
