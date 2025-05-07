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

namespace Google\Service\Fcmdata;

class GoogleFirebaseFcmDataV1beta1Data extends \Google\Model
{
  /**
   * @var string
   */
  public $countMessagesAccepted;
  /**
   * @var string
   */
  public $countNotificationsAccepted;
  protected $deliveryPerformancePercentsType = GoogleFirebaseFcmDataV1beta1DeliveryPerformancePercents::class;
  protected $deliveryPerformancePercentsDataType = '';
  protected $messageInsightPercentsType = GoogleFirebaseFcmDataV1beta1MessageInsightPercents::class;
  protected $messageInsightPercentsDataType = '';
  protected $messageOutcomePercentsType = GoogleFirebaseFcmDataV1beta1MessageOutcomePercents::class;
  protected $messageOutcomePercentsDataType = '';
  protected $proxyNotificationInsightPercentsType = GoogleFirebaseFcmDataV1beta1ProxyNotificationInsightPercents::class;
  protected $proxyNotificationInsightPercentsDataType = '';

  /**
   * @param string
   */
  public function setCountMessagesAccepted($countMessagesAccepted)
  {
    $this->countMessagesAccepted = $countMessagesAccepted;
  }
  /**
   * @return string
   */
  public function getCountMessagesAccepted()
  {
    return $this->countMessagesAccepted;
  }
  /**
   * @param string
   */
  public function setCountNotificationsAccepted($countNotificationsAccepted)
  {
    $this->countNotificationsAccepted = $countNotificationsAccepted;
  }
  /**
   * @return string
   */
  public function getCountNotificationsAccepted()
  {
    return $this->countNotificationsAccepted;
  }
  /**
   * @param GoogleFirebaseFcmDataV1beta1DeliveryPerformancePercents
   */
  public function setDeliveryPerformancePercents(GoogleFirebaseFcmDataV1beta1DeliveryPerformancePercents $deliveryPerformancePercents)
  {
    $this->deliveryPerformancePercents = $deliveryPerformancePercents;
  }
  /**
   * @return GoogleFirebaseFcmDataV1beta1DeliveryPerformancePercents
   */
  public function getDeliveryPerformancePercents()
  {
    return $this->deliveryPerformancePercents;
  }
  /**
   * @param GoogleFirebaseFcmDataV1beta1MessageInsightPercents
   */
  public function setMessageInsightPercents(GoogleFirebaseFcmDataV1beta1MessageInsightPercents $messageInsightPercents)
  {
    $this->messageInsightPercents = $messageInsightPercents;
  }
  /**
   * @return GoogleFirebaseFcmDataV1beta1MessageInsightPercents
   */
  public function getMessageInsightPercents()
  {
    return $this->messageInsightPercents;
  }
  /**
   * @param GoogleFirebaseFcmDataV1beta1MessageOutcomePercents
   */
  public function setMessageOutcomePercents(GoogleFirebaseFcmDataV1beta1MessageOutcomePercents $messageOutcomePercents)
  {
    $this->messageOutcomePercents = $messageOutcomePercents;
  }
  /**
   * @return GoogleFirebaseFcmDataV1beta1MessageOutcomePercents
   */
  public function getMessageOutcomePercents()
  {
    return $this->messageOutcomePercents;
  }
  /**
   * @param GoogleFirebaseFcmDataV1beta1ProxyNotificationInsightPercents
   */
  public function setProxyNotificationInsightPercents(GoogleFirebaseFcmDataV1beta1ProxyNotificationInsightPercents $proxyNotificationInsightPercents)
  {
    $this->proxyNotificationInsightPercents = $proxyNotificationInsightPercents;
  }
  /**
   * @return GoogleFirebaseFcmDataV1beta1ProxyNotificationInsightPercents
   */
  public function getProxyNotificationInsightPercents()
  {
    return $this->proxyNotificationInsightPercents;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirebaseFcmDataV1beta1Data::class, 'Google_Service_Fcmdata_GoogleFirebaseFcmDataV1beta1Data');
