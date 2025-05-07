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

namespace Google\Service\DLP;

class GooglePrivacyDlpV2DataProfileAction extends \Google\Model
{
  protected $exportDataType = GooglePrivacyDlpV2Export::class;
  protected $exportDataDataType = '';
  protected $pubSubNotificationType = GooglePrivacyDlpV2PubSubNotification::class;
  protected $pubSubNotificationDataType = '';
  protected $publishToChronicleType = GooglePrivacyDlpV2PublishToChronicle::class;
  protected $publishToChronicleDataType = '';
  protected $publishToSccType = GooglePrivacyDlpV2PublishToSecurityCommandCenter::class;
  protected $publishToSccDataType = '';
  protected $tagResourcesType = GooglePrivacyDlpV2TagResources::class;
  protected $tagResourcesDataType = '';

  /**
   * @param GooglePrivacyDlpV2Export
   */
  public function setExportData(GooglePrivacyDlpV2Export $exportData)
  {
    $this->exportData = $exportData;
  }
  /**
   * @return GooglePrivacyDlpV2Export
   */
  public function getExportData()
  {
    return $this->exportData;
  }
  /**
   * @param GooglePrivacyDlpV2PubSubNotification
   */
  public function setPubSubNotification(GooglePrivacyDlpV2PubSubNotification $pubSubNotification)
  {
    $this->pubSubNotification = $pubSubNotification;
  }
  /**
   * @return GooglePrivacyDlpV2PubSubNotification
   */
  public function getPubSubNotification()
  {
    return $this->pubSubNotification;
  }
  /**
   * @param GooglePrivacyDlpV2PublishToChronicle
   */
  public function setPublishToChronicle(GooglePrivacyDlpV2PublishToChronicle $publishToChronicle)
  {
    $this->publishToChronicle = $publishToChronicle;
  }
  /**
   * @return GooglePrivacyDlpV2PublishToChronicle
   */
  public function getPublishToChronicle()
  {
    return $this->publishToChronicle;
  }
  /**
   * @param GooglePrivacyDlpV2PublishToSecurityCommandCenter
   */
  public function setPublishToScc(GooglePrivacyDlpV2PublishToSecurityCommandCenter $publishToScc)
  {
    $this->publishToScc = $publishToScc;
  }
  /**
   * @return GooglePrivacyDlpV2PublishToSecurityCommandCenter
   */
  public function getPublishToScc()
  {
    return $this->publishToScc;
  }
  /**
   * @param GooglePrivacyDlpV2TagResources
   */
  public function setTagResources(GooglePrivacyDlpV2TagResources $tagResources)
  {
    $this->tagResources = $tagResources;
  }
  /**
   * @return GooglePrivacyDlpV2TagResources
   */
  public function getTagResources()
  {
    return $this->tagResources;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePrivacyDlpV2DataProfileAction::class, 'Google_Service_DLP_GooglePrivacyDlpV2DataProfileAction');
