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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1ReinforcementLearningDataStats extends \Google\Model
{
  protected $preferenceDatasetStatsType = GoogleCloudAiplatformV1DatasetStats::class;
  protected $preferenceDatasetStatsDataType = '';
  protected $promptDatasetStatsType = GoogleCloudAiplatformV1DatasetStats::class;
  protected $promptDatasetStatsDataType = '';

  /**
   * @param GoogleCloudAiplatformV1DatasetStats
   */
  public function setPreferenceDatasetStats(GoogleCloudAiplatformV1DatasetStats $preferenceDatasetStats)
  {
    $this->preferenceDatasetStats = $preferenceDatasetStats;
  }
  /**
   * @return GoogleCloudAiplatformV1DatasetStats
   */
  public function getPreferenceDatasetStats()
  {
    return $this->preferenceDatasetStats;
  }
  /**
   * @param GoogleCloudAiplatformV1DatasetStats
   */
  public function setPromptDatasetStats(GoogleCloudAiplatformV1DatasetStats $promptDatasetStats)
  {
    $this->promptDatasetStats = $promptDatasetStats;
  }
  /**
   * @return GoogleCloudAiplatformV1DatasetStats
   */
  public function getPromptDatasetStats()
  {
    return $this->promptDatasetStats;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1ReinforcementLearningDataStats::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1ReinforcementLearningDataStats');
