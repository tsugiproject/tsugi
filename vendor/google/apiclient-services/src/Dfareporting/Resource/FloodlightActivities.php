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

namespace Google\Service\Dfareporting\Resource;

use Google\Service\Dfareporting\FloodlightActivitiesGenerateTagResponse;
use Google\Service\Dfareporting\FloodlightActivitiesListResponse;
use Google\Service\Dfareporting\FloodlightActivity;

/**
 * The "floodlightActivities" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google\Service\Dfareporting(...);
 *   $floodlightActivities = $dfareportingService->floodlightActivities;
 *  </code>
 */
class FloodlightActivities extends \Google\Service\Resource
{
  /**
   * Deletes an existing floodlight activity. (floodlightActivities.delete)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity ID.
   * @param array $optParams Optional parameters.
   * @throws \Google\Service\Exception
   */
  public function delete($profileId, $id, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'id' => $id];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params]);
  }
  /**
   * Generates a tag for a floodlight activity. (floodlightActivities.generatetag)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string floodlightActivityId Floodlight activity ID for which we
   * want to generate a tag.
   * @return FloodlightActivitiesGenerateTagResponse
   * @throws \Google\Service\Exception
   */
  public function generatetag($profileId, $optParams = [])
  {
    $params = ['profileId' => $profileId];
    $params = array_merge($params, $optParams);
    return $this->call('generatetag', [$params], FloodlightActivitiesGenerateTagResponse::class);
  }
  /**
   * Gets one floodlight activity by ID. (floodlightActivities.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Floodlight activity ID.
   * @param array $optParams Optional parameters.
   * @return FloodlightActivity
   * @throws \Google\Service\Exception
   */
  public function get($profileId, $id, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'id' => $id];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], FloodlightActivity::class);
  }
  /**
   * Inserts a new floodlight activity. (floodlightActivities.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param FloodlightActivity $postBody
   * @param array $optParams Optional parameters.
   * @return FloodlightActivity
   * @throws \Google\Service\Exception
   */
  public function insert($profileId, FloodlightActivity $postBody, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('insert', [$params], FloodlightActivity::class);
  }
  /**
   * Retrieves a list of floodlight activities, possibly filtered. This method
   * supports paging. (floodlightActivities.listFloodlightActivities)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string advertiserId Select only floodlight activities for the
   * specified advertiser ID. Must specify either ids, advertiserId, or
   * floodlightConfigurationId for a non-empty result.
   * @opt_param string floodlightActivityGroupIds Select only floodlight
   * activities with the specified floodlight activity group IDs.
   * @opt_param string floodlightActivityGroupName Select only floodlight
   * activities with the specified floodlight activity group name.
   * @opt_param string floodlightActivityGroupTagString Select only floodlight
   * activities with the specified floodlight activity group tag string.
   * @opt_param string floodlightActivityGroupType Select only floodlight
   * activities with the specified floodlight activity group type.
   * @opt_param string floodlightConfigurationId Select only floodlight activities
   * for the specified floodlight configuration ID. Must specify either ids,
   * advertiserId, or floodlightConfigurationId for a non-empty result.
   * @opt_param string ids Select only floodlight activities with the specified
   * IDs. Must specify either ids, advertiserId, or floodlightConfigurationId for
   * a non-empty result.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "floodlightactivity*2015" will return
   * objects with names like "floodlightactivity June 2015", "floodlightactivity
   * April 2015", or simply "floodlightactivity 2015". Most of the searches also
   * add wildcards implicitly at the start and the end of the search string. For
   * example, a search string of "floodlightactivity" will match objects with name
   * "my floodlightactivity activity", "floodlightactivity 2015", or simply
   * "floodlightactivity".
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string sortOrder Order of sorted results.
   * @opt_param string tagString Select only floodlight activities with the
   * specified tag string.
   * @return FloodlightActivitiesListResponse
   * @throws \Google\Service\Exception
   */
  public function listFloodlightActivities($profileId, $optParams = [])
  {
    $params = ['profileId' => $profileId];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], FloodlightActivitiesListResponse::class);
  }
  /**
   * Updates an existing floodlight activity. This method supports patch
   * semantics. (floodlightActivities.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Required. EventTag ID.
   * @param FloodlightActivity $postBody
   * @param array $optParams Optional parameters.
   * @return FloodlightActivity
   * @throws \Google\Service\Exception
   */
  public function patch($profileId, $id, FloodlightActivity $postBody, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'id' => $id, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], FloodlightActivity::class);
  }
  /**
   * Updates an existing floodlight activity. (floodlightActivities.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param FloodlightActivity $postBody
   * @param array $optParams Optional parameters.
   * @return FloodlightActivity
   * @throws \Google\Service\Exception
   */
  public function update($profileId, FloodlightActivity $postBody, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('update', [$params], FloodlightActivity::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FloodlightActivities::class, 'Google_Service_Dfareporting_Resource_FloodlightActivities');
