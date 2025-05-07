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

namespace Google\Service\AnalyticsHub;

class GooglePubsubV1Subscription extends \Google\Collection
{
  protected $collection_key = 'messageTransforms';
  /**
   * @var int
   */
  public $ackDeadlineSeconds;
  protected $analyticsHubSubscriptionInfoType = AnalyticsHubSubscriptionInfo::class;
  protected $analyticsHubSubscriptionInfoDataType = '';
  protected $bigqueryConfigType = BigQueryConfig::class;
  protected $bigqueryConfigDataType = '';
  protected $cloudStorageConfigType = CloudStorageConfig::class;
  protected $cloudStorageConfigDataType = '';
  protected $deadLetterPolicyType = DeadLetterPolicy::class;
  protected $deadLetterPolicyDataType = '';
  /**
   * @var bool
   */
  public $detached;
  /**
   * @var bool
   */
  public $enableExactlyOnceDelivery;
  /**
   * @var bool
   */
  public $enableMessageOrdering;
  protected $expirationPolicyType = ExpirationPolicy::class;
  protected $expirationPolicyDataType = '';
  /**
   * @var string
   */
  public $filter;
  /**
   * @var string[]
   */
  public $labels;
  /**
   * @var string
   */
  public $messageRetentionDuration;
  protected $messageTransformsType = MessageTransform::class;
  protected $messageTransformsDataType = 'array';
  /**
   * @var string
   */
  public $name;
  protected $pushConfigType = PushConfig::class;
  protected $pushConfigDataType = '';
  /**
   * @var bool
   */
  public $retainAckedMessages;
  protected $retryPolicyType = RetryPolicy::class;
  protected $retryPolicyDataType = '';
  /**
   * @var string
   */
  public $state;
  /**
   * @var string
   */
  public $topicMessageRetentionDuration;

  /**
   * @param int
   */
  public function setAckDeadlineSeconds($ackDeadlineSeconds)
  {
    $this->ackDeadlineSeconds = $ackDeadlineSeconds;
  }
  /**
   * @return int
   */
  public function getAckDeadlineSeconds()
  {
    return $this->ackDeadlineSeconds;
  }
  /**
   * @param AnalyticsHubSubscriptionInfo
   */
  public function setAnalyticsHubSubscriptionInfo(AnalyticsHubSubscriptionInfo $analyticsHubSubscriptionInfo)
  {
    $this->analyticsHubSubscriptionInfo = $analyticsHubSubscriptionInfo;
  }
  /**
   * @return AnalyticsHubSubscriptionInfo
   */
  public function getAnalyticsHubSubscriptionInfo()
  {
    return $this->analyticsHubSubscriptionInfo;
  }
  /**
   * @param BigQueryConfig
   */
  public function setBigqueryConfig(BigQueryConfig $bigqueryConfig)
  {
    $this->bigqueryConfig = $bigqueryConfig;
  }
  /**
   * @return BigQueryConfig
   */
  public function getBigqueryConfig()
  {
    return $this->bigqueryConfig;
  }
  /**
   * @param CloudStorageConfig
   */
  public function setCloudStorageConfig(CloudStorageConfig $cloudStorageConfig)
  {
    $this->cloudStorageConfig = $cloudStorageConfig;
  }
  /**
   * @return CloudStorageConfig
   */
  public function getCloudStorageConfig()
  {
    return $this->cloudStorageConfig;
  }
  /**
   * @param DeadLetterPolicy
   */
  public function setDeadLetterPolicy(DeadLetterPolicy $deadLetterPolicy)
  {
    $this->deadLetterPolicy = $deadLetterPolicy;
  }
  /**
   * @return DeadLetterPolicy
   */
  public function getDeadLetterPolicy()
  {
    return $this->deadLetterPolicy;
  }
  /**
   * @param bool
   */
  public function setDetached($detached)
  {
    $this->detached = $detached;
  }
  /**
   * @return bool
   */
  public function getDetached()
  {
    return $this->detached;
  }
  /**
   * @param bool
   */
  public function setEnableExactlyOnceDelivery($enableExactlyOnceDelivery)
  {
    $this->enableExactlyOnceDelivery = $enableExactlyOnceDelivery;
  }
  /**
   * @return bool
   */
  public function getEnableExactlyOnceDelivery()
  {
    return $this->enableExactlyOnceDelivery;
  }
  /**
   * @param bool
   */
  public function setEnableMessageOrdering($enableMessageOrdering)
  {
    $this->enableMessageOrdering = $enableMessageOrdering;
  }
  /**
   * @return bool
   */
  public function getEnableMessageOrdering()
  {
    return $this->enableMessageOrdering;
  }
  /**
   * @param ExpirationPolicy
   */
  public function setExpirationPolicy(ExpirationPolicy $expirationPolicy)
  {
    $this->expirationPolicy = $expirationPolicy;
  }
  /**
   * @return ExpirationPolicy
   */
  public function getExpirationPolicy()
  {
    return $this->expirationPolicy;
  }
  /**
   * @param string
   */
  public function setFilter($filter)
  {
    $this->filter = $filter;
  }
  /**
   * @return string
   */
  public function getFilter()
  {
    return $this->filter;
  }
  /**
   * @param string[]
   */
  public function setLabels($labels)
  {
    $this->labels = $labels;
  }
  /**
   * @return string[]
   */
  public function getLabels()
  {
    return $this->labels;
  }
  /**
   * @param string
   */
  public function setMessageRetentionDuration($messageRetentionDuration)
  {
    $this->messageRetentionDuration = $messageRetentionDuration;
  }
  /**
   * @return string
   */
  public function getMessageRetentionDuration()
  {
    return $this->messageRetentionDuration;
  }
  /**
   * @param MessageTransform[]
   */
  public function setMessageTransforms($messageTransforms)
  {
    $this->messageTransforms = $messageTransforms;
  }
  /**
   * @return MessageTransform[]
   */
  public function getMessageTransforms()
  {
    return $this->messageTransforms;
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
   * @param PushConfig
   */
  public function setPushConfig(PushConfig $pushConfig)
  {
    $this->pushConfig = $pushConfig;
  }
  /**
   * @return PushConfig
   */
  public function getPushConfig()
  {
    return $this->pushConfig;
  }
  /**
   * @param bool
   */
  public function setRetainAckedMessages($retainAckedMessages)
  {
    $this->retainAckedMessages = $retainAckedMessages;
  }
  /**
   * @return bool
   */
  public function getRetainAckedMessages()
  {
    return $this->retainAckedMessages;
  }
  /**
   * @param RetryPolicy
   */
  public function setRetryPolicy(RetryPolicy $retryPolicy)
  {
    $this->retryPolicy = $retryPolicy;
  }
  /**
   * @return RetryPolicy
   */
  public function getRetryPolicy()
  {
    return $this->retryPolicy;
  }
  /**
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
  /**
   * @param string
   */
  public function setTopicMessageRetentionDuration($topicMessageRetentionDuration)
  {
    $this->topicMessageRetentionDuration = $topicMessageRetentionDuration;
  }
  /**
   * @return string
   */
  public function getTopicMessageRetentionDuration()
  {
    return $this->topicMessageRetentionDuration;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GooglePubsubV1Subscription::class, 'Google_Service_AnalyticsHub_GooglePubsubV1Subscription');
