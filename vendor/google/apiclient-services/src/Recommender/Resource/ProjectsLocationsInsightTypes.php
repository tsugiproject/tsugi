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

namespace Google\Service\Recommender\Resource;

use Google\Service\Recommender\GoogleCloudRecommenderV1InsightTypeConfig;

/**
 * The "insightTypes" collection of methods.
 * Typical usage is:
 *  <code>
 *   $recommenderService = new Google\Service\Recommender(...);
 *   $insightTypes = $recommenderService->projects_locations_insightTypes;
 *  </code>
 */
class ProjectsLocationsInsightTypes extends \Google\Service\Resource
{
  /**
   * Gets the requested InsightTypeConfig. There is only one instance of the
   * config for each InsightType. (insightTypes.getConfig)
   *
   * @param string $name Required. Name of the InsightTypeConfig to get.
   * Acceptable formats: * `projects/[PROJECT_NUMBER]/locations/[LOCATION]/insight
   * Types/[INSIGHT_TYPE_ID]/config` * `projects/[PROJECT_ID]/locations/[LOCATION]
   * /insightTypes/[INSIGHT_TYPE_ID]/config` * `organizations/[ORGANIZATION_ID]/lo
   * cations/[LOCATION]/insightTypes/[INSIGHT_TYPE_ID]/config` * `billingAccounts/
   * [BILLING_ACCOUNT_ID]/locations/[LOCATION]/insightTypes/[INSIGHT_TYPE_ID]/conf
   * ig`
   * @param array $optParams Optional parameters.
   * @return GoogleCloudRecommenderV1InsightTypeConfig
   * @throws \Google\Service\Exception
   */
  public function getConfig($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('getConfig', [$params], GoogleCloudRecommenderV1InsightTypeConfig::class);
  }
  /**
   * Updates an InsightTypeConfig change. This will create a new revision of the
   * config. (insightTypes.updateConfig)
   *
   * @param string $name Identifier. Name of insight type config. Eg, projects/[PR
   * OJECT_NUMBER]/locations/[LOCATION]/insightTypes/[INSIGHT_TYPE_ID]/config
   * @param GoogleCloudRecommenderV1InsightTypeConfig $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string updateMask The list of fields to be updated.
   * @opt_param bool validateOnly If true, validate the request and preview the
   * change, but do not actually update it.
   * @return GoogleCloudRecommenderV1InsightTypeConfig
   * @throws \Google\Service\Exception
   */
  public function updateConfig($name, GoogleCloudRecommenderV1InsightTypeConfig $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('updateConfig', [$params], GoogleCloudRecommenderV1InsightTypeConfig::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsInsightTypes::class, 'Google_Service_Recommender_Resource_ProjectsLocationsInsightTypes');
