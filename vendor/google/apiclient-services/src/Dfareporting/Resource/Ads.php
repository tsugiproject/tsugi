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

use Google\Service\Dfareporting\Ad;
use Google\Service\Dfareporting\AdsListResponse;

/**
 * The "ads" collection of methods.
 * Typical usage is:
 *  <code>
 *   $dfareportingService = new Google\Service\Dfareporting(...);
 *   $ads = $dfareportingService->ads;
 *  </code>
 */
class Ads extends \Google\Service\Resource
{
  /**
   * Gets one ad by ID. (ads.get)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Ad ID.
   * @param array $optParams Optional parameters.
   * @return Ad
   * @throws \Google\Service\Exception
   */
  public function get($profileId, $id, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'id' => $id];
    $params = array_merge($params, $optParams);
    return $this->call('get', [$params], Ad::class);
  }
  /**
   * Inserts a new ad. (ads.insert)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Ad $postBody
   * @param array $optParams Optional parameters.
   * @return Ad
   * @throws \Google\Service\Exception
   */
  public function insert($profileId, Ad $postBody, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('insert', [$params], Ad::class);
  }
  /**
   * Retrieves a list of ads, possibly filtered. This method supports paging.
   * (ads.listAds)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param array $optParams Optional parameters.
   *
   * @opt_param bool active Select only active ads.
   * @opt_param string advertiserId Select only ads with this advertiser ID.
   * @opt_param bool archived Select only archived ads.
   * @opt_param string audienceSegmentIds Select only ads with these audience
   * segment IDs.
   * @opt_param string campaignIds Select only ads with these campaign IDs.
   * @opt_param string compatibility Select default ads with the specified
   * compatibility. Applicable when type is AD_SERVING_DEFAULT_AD. DISPLAY and
   * DISPLAY_INTERSTITIAL refer to rendering either on desktop or on mobile
   * devices for regular or interstitial ads, respectively. APP and
   * APP_INTERSTITIAL are for rendering in mobile apps. IN_STREAM_VIDEO refers to
   * rendering an in-stream video ads developed with the VAST standard.
   * @opt_param string creativeIds Select only ads with these creative IDs
   * assigned.
   * @opt_param string creativeOptimizationConfigurationIds Select only ads with
   * these creative optimization configuration IDs.
   * @opt_param bool dynamicClickTracker Select only dynamic click trackers.
   * Applicable when type is AD_SERVING_CLICK_TRACKER. If true, select dynamic
   * click trackers. If false, select static click trackers. Leave unset to select
   * both.
   * @opt_param string ids Select only ads with these IDs.
   * @opt_param string landingPageIds Select only ads with these landing page IDs.
   * @opt_param int maxResults Maximum number of results to return.
   * @opt_param string overriddenEventTagId Select only ads with this event tag
   * override ID.
   * @opt_param string pageToken Value of the nextPageToken from the previous
   * result page.
   * @opt_param string placementIds Select only ads with these placement IDs
   * assigned.
   * @opt_param string remarketingListIds Select only ads whose list targeting
   * expression use these remarketing list IDs.
   * @opt_param string searchString Allows searching for objects by name or ID.
   * Wildcards (*) are allowed. For example, "ad*2015" will return objects with
   * names like "ad June 2015", "ad April 2015", or simply "ad 2015". Most of the
   * searches also add wildcards implicitly at the start and the end of the search
   * string. For example, a search string of "ad" will match objects with name "my
   * ad", "ad 2015", or simply "ad".
   * @opt_param string sizeIds Select only ads with these size IDs.
   * @opt_param string sortField Field by which to sort the list.
   * @opt_param string sortOrder Order of sorted results.
   * @opt_param bool sslCompliant Select only ads that are SSL-compliant.
   * @opt_param bool sslRequired Select only ads that require SSL.
   * @opt_param string type Select only ads with these types.
   * @return AdsListResponse
   * @throws \Google\Service\Exception
   */
  public function listAds($profileId, $optParams = [])
  {
    $params = ['profileId' => $profileId];
    $params = array_merge($params, $optParams);
    return $this->call('list', [$params], AdsListResponse::class);
  }
  /**
   * Updates an existing ad. This method supports patch semantics. (ads.patch)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param string $id Required. RemarketingList ID.
   * @param Ad $postBody
   * @param array $optParams Optional parameters.
   * @return Ad
   * @throws \Google\Service\Exception
   */
  public function patch($profileId, $id, Ad $postBody, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'id' => $id, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], Ad::class);
  }
  /**
   * Updates an existing ad. (ads.update)
   *
   * @param string $profileId User profile ID associated with this request.
   * @param Ad $postBody
   * @param array $optParams Optional parameters.
   * @return Ad
   * @throws \Google\Service\Exception
   */
  public function update($profileId, Ad $postBody, $optParams = [])
  {
    $params = ['profileId' => $profileId, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('update', [$params], Ad::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Ads::class, 'Google_Service_Dfareporting_Resource_Ads');
