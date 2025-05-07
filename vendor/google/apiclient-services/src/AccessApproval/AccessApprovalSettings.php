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

namespace Google\Service\AccessApproval;

class AccessApprovalSettings extends \Google\Collection
{
  protected $collection_key = 'notificationEmails';
  /**
   * @var string
   */
  public $activeKeyVersion;
  /**
   * @var bool
   */
  public $ancestorHasActiveKeyVersion;
  /**
   * @var bool
   */
  public $enrolledAncestor;
  protected $enrolledServicesType = EnrolledService::class;
  protected $enrolledServicesDataType = 'array';
  /**
   * @var bool
   */
  public $invalidKeyVersion;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string[]
   */
  public $notificationEmails;
  /**
   * @var string
   */
  public $notificationPubsubTopic;
  /**
   * @var bool
   */
  public $preferNoBroadApprovalRequests;
  /**
   * @var int
   */
  public $preferredRequestExpirationDays;
  /**
   * @var string
   */
  public $requestScopeMaxWidthPreference;
  /**
   * @var bool
   */
  public $requireCustomerVisibleJustification;

  /**
   * @param string
   */
  public function setActiveKeyVersion($activeKeyVersion)
  {
    $this->activeKeyVersion = $activeKeyVersion;
  }
  /**
   * @return string
   */
  public function getActiveKeyVersion()
  {
    return $this->activeKeyVersion;
  }
  /**
   * @param bool
   */
  public function setAncestorHasActiveKeyVersion($ancestorHasActiveKeyVersion)
  {
    $this->ancestorHasActiveKeyVersion = $ancestorHasActiveKeyVersion;
  }
  /**
   * @return bool
   */
  public function getAncestorHasActiveKeyVersion()
  {
    return $this->ancestorHasActiveKeyVersion;
  }
  /**
   * @param bool
   */
  public function setEnrolledAncestor($enrolledAncestor)
  {
    $this->enrolledAncestor = $enrolledAncestor;
  }
  /**
   * @return bool
   */
  public function getEnrolledAncestor()
  {
    return $this->enrolledAncestor;
  }
  /**
   * @param EnrolledService[]
   */
  public function setEnrolledServices($enrolledServices)
  {
    $this->enrolledServices = $enrolledServices;
  }
  /**
   * @return EnrolledService[]
   */
  public function getEnrolledServices()
  {
    return $this->enrolledServices;
  }
  /**
   * @param bool
   */
  public function setInvalidKeyVersion($invalidKeyVersion)
  {
    $this->invalidKeyVersion = $invalidKeyVersion;
  }
  /**
   * @return bool
   */
  public function getInvalidKeyVersion()
  {
    return $this->invalidKeyVersion;
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
   * @param string[]
   */
  public function setNotificationEmails($notificationEmails)
  {
    $this->notificationEmails = $notificationEmails;
  }
  /**
   * @return string[]
   */
  public function getNotificationEmails()
  {
    return $this->notificationEmails;
  }
  /**
   * @param string
   */
  public function setNotificationPubsubTopic($notificationPubsubTopic)
  {
    $this->notificationPubsubTopic = $notificationPubsubTopic;
  }
  /**
   * @return string
   */
  public function getNotificationPubsubTopic()
  {
    return $this->notificationPubsubTopic;
  }
  /**
   * @param bool
   */
  public function setPreferNoBroadApprovalRequests($preferNoBroadApprovalRequests)
  {
    $this->preferNoBroadApprovalRequests = $preferNoBroadApprovalRequests;
  }
  /**
   * @return bool
   */
  public function getPreferNoBroadApprovalRequests()
  {
    return $this->preferNoBroadApprovalRequests;
  }
  /**
   * @param int
   */
  public function setPreferredRequestExpirationDays($preferredRequestExpirationDays)
  {
    $this->preferredRequestExpirationDays = $preferredRequestExpirationDays;
  }
  /**
   * @return int
   */
  public function getPreferredRequestExpirationDays()
  {
    return $this->preferredRequestExpirationDays;
  }
  /**
   * @param string
   */
  public function setRequestScopeMaxWidthPreference($requestScopeMaxWidthPreference)
  {
    $this->requestScopeMaxWidthPreference = $requestScopeMaxWidthPreference;
  }
  /**
   * @return string
   */
  public function getRequestScopeMaxWidthPreference()
  {
    return $this->requestScopeMaxWidthPreference;
  }
  /**
   * @param bool
   */
  public function setRequireCustomerVisibleJustification($requireCustomerVisibleJustification)
  {
    $this->requireCustomerVisibleJustification = $requireCustomerVisibleJustification;
  }
  /**
   * @return bool
   */
  public function getRequireCustomerVisibleJustification()
  {
    return $this->requireCustomerVisibleJustification;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AccessApprovalSettings::class, 'Google_Service_AccessApproval_AccessApprovalSettings');
