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

namespace Google\Service\Aiplatform\Resource;

use Google\Service\Aiplatform\GoogleCloudAiplatformV1ImportRagFilesRequest;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1ListRagFilesResponse;
use Google\Service\Aiplatform\GoogleCloudAiplatformV1RagFile;
use Google\Service\Aiplatform\GoogleLongrunningOperation;

/**
 * The "ragFiles" collection of methods.
 * Typical usage is:
 *  <code>
 *   $aiplatformService = new Google\Service\Aiplatform(...);
 *   $ragFiles = $aiplatformService->projects_locations_ragCorpora_ragFiles;
 *  </code>
 */
class ProjectsLocationsRagCorporaRagFiles extends \Google\Service\Resource
{
  /**
   * Deletes a RagFile. (ragFiles.delete)
   *
   * @param string $name Required. The name of the RagFile resource to be deleted.
   * Format: `projects/{project}/locations/{location}/ragCorpora/{rag_corpus}/ragF
   * iles/{rag_file}`
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Gets a RagFile. (ragFiles.get)
   *
   * @param string $name Required. The name of the RagFile resource. Format: `proj
   * ects/{project}/locations/{location}/ragCorpora/{rag_corpus}/ragFiles/{rag_fil
   * e}`
   * @param array $optParams Optional parameters.
   * @return GoogleCloudAiplatformV1RagFile
   * @throws \Google\Service\Exception
   */
  public function get($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], GoogleCloudAiplatformV1RagFile::class);
  }
  /**
   * Import files from Google Cloud Storage or Google Drive into a RagCorpus.
   * (ragFiles.import)
   *
   * @param string $parent Required. The name of the RagCorpus resource into which
   * to import files. Format:
   * `projects/{project}/locations/{location}/ragCorpora/{rag_corpus}`
   * @param GoogleCloudAiplatformV1ImportRagFilesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function import($parent, GoogleCloudAiplatformV1ImportRagFilesRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('import', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Lists RagFiles in a RagCorpus.
   * (ragFiles.listProjectsLocationsRagCorporaRagFiles)
   *
   * @param string $parent Required. The resource name of the RagCorpus from which
   * to list the RagFiles. Format:
   * `projects/{project}/locations/{location}/ragCorpora/{rag_corpus}`
   * @param array $optParams Optional parameters.
   *
   * @opt_param int pageSize Optional. The standard list page size.
   * @opt_param string pageToken Optional. The standard list page token. Typically
   * obtained via ListRagFilesResponse.next_page_token of the previous
   * VertexRagDataService.ListRagFiles call.
   * @return GoogleCloudAiplatformV1ListRagFilesResponse
   * @throws \Google\Service\Exception
   */
  public function listProjectsLocationsRagCorporaRagFiles($parent, $optParams = [])
  {
    $params = ['parent' => $parent];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], GoogleCloudAiplatformV1ListRagFilesResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsRagCorporaRagFiles::class, 'Google_Service_Aiplatform_Resource_ProjectsLocationsRagCorporaRagFiles');
