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

use Google\Service\ArtifactRegistry\UploadGenericArtifactMediaResponse;
use Google\Service\ArtifactRegistry\UploadGenericArtifactRequest;

/**
 * The "genericArtifacts" collection of methods.
 * Typical usage is:
 *  <code>
 *   $artifactregistryService = new Google\Service\ArtifactRegistry(...);
 *   $genericArtifacts = $artifactregistryService->projects_locations_repositories_genericArtifacts;
 *  </code>
 */
class ProjectsLocationsRepositoriesGenericArtifacts extends \Google\Service\Resource
{
  /**
   * Directly uploads a Generic artifact. The returned operation will complete
   * once the resources are uploaded. Package, version, and file resources are
   * created based on the uploaded artifact. Uploaded artifacts that conflict with
   * existing resources will raise an `ALREADY_EXISTS` error.
   * (genericArtifacts.upload)
   *
   * @param string $parent The resource name of the repository where the generic
   * artifact will be uploaded.
   * @param UploadGenericArtifactRequest $postBody
   * @param array $optParams Optional parameters.
   * @return UploadGenericArtifactMediaResponse
   * @throws \Google\Service\Exception
   */
  public function upload($parent, UploadGenericArtifactRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('upload', [$params], UploadGenericArtifactMediaResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsRepositoriesGenericArtifacts::class, 'Google_Service_ArtifactRegistry_Resource_ProjectsLocationsRepositoriesGenericArtifacts');
