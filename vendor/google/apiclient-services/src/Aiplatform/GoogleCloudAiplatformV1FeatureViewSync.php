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

class GoogleCloudAiplatformV1FeatureViewSync extends \Google\Model
{
  /**
   * @var string
   */
  public $createTime;
  protected $finalStatusType = GoogleRpcStatus::class;
  protected $finalStatusDataType = '';
  /**
   * @var string
   */
  public $name;
  protected $runTimeType = GoogleTypeInterval::class;
  protected $runTimeDataType = '';
  /**
   * @var bool
   */
  public $satisfiesPzi;
  /**
   * @var bool
   */
  public $satisfiesPzs;
  protected $syncSummaryType = GoogleCloudAiplatformV1FeatureViewSyncSyncSummary::class;
  protected $syncSummaryDataType = '';

  /**
   * @param string
   */
  public function setCreateTime($createTime)
  {
    $this->createTime = $createTime;
  }
  /**
   * @return string
   */
  public function getCreateTime()
  {
    return $this->createTime;
  }
  /**
   * @param GoogleRpcStatus
   */
  public function setFinalStatus(GoogleRpcStatus $finalStatus)
  {
    $this->finalStatus = $finalStatus;
  }
  /**
   * @return GoogleRpcStatus
   */
  public function getFinalStatus()
  {
    return $this->finalStatus;
  }
  /**
   * @param string
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * @param GoogleTypeInterval
   */
  public function setRunTime(GoogleTypeInterval $runTime)
  {
    $this->runTime = $runTime;
  }
  /**
   * @return GoogleTypeInterval
   */
  public function getRunTime()
  {
    return $this->runTime;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzi($satisfiesPzi)
  {
    $this->satisfiesPzi = $satisfiesPzi;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzi()
  {
    return $this->satisfiesPzi;
  }
  /**
   * @param bool
   */
  public function setSatisfiesPzs($satisfiesPzs)
  {
    $this->satisfiesPzs = $satisfiesPzs;
  }
  /**
   * @return bool
   */
  public function getSatisfiesPzs()
  {
    return $this->satisfiesPzs;
  }
  /**
   * @param GoogleCloudAiplatformV1FeatureViewSyncSyncSummary
   */
  public function setSyncSummary(GoogleCloudAiplatformV1FeatureViewSyncSyncSummary $syncSummary)
  {
    $this->syncSummary = $syncSummary;
  }
  /**
   * @return GoogleCloudAiplatformV1FeatureViewSyncSyncSummary
   */
  public function getSyncSummary()
  {
    return $this->syncSummary;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1FeatureViewSync::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1FeatureViewSync');
