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

namespace Google\Service\DiscoveryEngine\Resource;

use Google\Service\DiscoveryEngine\GoogleCloudDiscoveryengineV1ImportCompletionSuggestionsRequest;
use Google\Service\DiscoveryEngine\GoogleCloudDiscoveryengineV1PurgeCompletionSuggestionsRequest;
use Google\Service\DiscoveryEngine\GoogleLongrunningOperation;

/**
 * The "completionSuggestions" collection of methods.
 * Typical usage is:
 *  <code>
 *   $discoveryengineService = new Google\Service\DiscoveryEngine(...);
 *   $completionSuggestions = $discoveryengineService->projects_locations_collections_dataStores_completionSuggestions;
 *  </code>
 */
class ProjectsLocationsCollectionsDataStoresCompletionSuggestions extends \Google\Service\Resource
{
  /**
   * Imports CompletionSuggestions for a DataStore. (completionSuggestions.import)
   *
   * @param string $parent Required. The parent data store resource name for which
   * to import customer autocomplete suggestions. Follows pattern
   * `projects/locations/collections/dataStores`
   * @param GoogleCloudDiscoveryengineV1ImportCompletionSuggestionsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function import($parent, GoogleCloudDiscoveryengineV1ImportCompletionSuggestionsRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('import', [$params], GoogleLongrunningOperation::class);
  }
  /**
   * Permanently deletes all CompletionSuggestions for a DataStore.
   * (completionSuggestions.purge)
   *
   * @param string $parent Required. The parent data store resource name for which
   * to purge completion suggestions. Follows pattern
   * projects/locations/collections/dataStores.
   * @param GoogleCloudDiscoveryengineV1PurgeCompletionSuggestionsRequest $postBody
   * @param array $optParams Optional parameters.
   * @return GoogleLongrunningOperation
   * @throws \Google\Service\Exception
   */
  public function purge($parent, GoogleCloudDiscoveryengineV1PurgeCompletionSuggestionsRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('purge', [$params], GoogleLongrunningOperation::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsCollectionsDataStoresCompletionSuggestions::class, 'Google_Service_DiscoveryEngine_Resource_ProjectsLocationsCollectionsDataStoresCompletionSuggestions');
