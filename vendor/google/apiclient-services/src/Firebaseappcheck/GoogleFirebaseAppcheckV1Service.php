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

namespace Google\Service\Firebaseappcheck;

class GoogleFirebaseAppcheckV1Service extends \Google\Model
{
  /**
   * The relevant App Check protection is not enforced for the service or
   * resource, nor are App Check metrics collected. Though the relevant App
   * Check protection is not applied, other applicable protections, such as user
   * authorization, are still enforced. An unconfigured protection is in this
   * mode by default.
   */
  public const ENFORCEMENT_MODE_OFF = 'OFF';
  /**
   * The relevant App Check protection is not enforced for the service or
   * resource. App Check metrics are collected to help you decide when to turn
   * on enforcement. These metrics will show the portion of traffic that is
   * deemed invalid by the relevant App Check protection, but that traffic will
   * not be rejected until you turn on enforcement. Though the relevant App
   * Check protection is not enforced, other applicable protections, such as
   * user authorization, are still enforced. Some services require certain
   * conditions to be met before they will work with App Check, such as
   * requiring you to upgrade to a specific service tier. Until those
   * requirements are met for a service, this `UNENFORCED` setting will have no
   * effect and App Check will not work with that service.
   */
  public const ENFORCEMENT_MODE_UNENFORCED = 'UNENFORCED';
  /**
   * The relevant App Check protection is enforced for the service or resource.
   * The service or resource will reject any traffic not accompanied by an App
   * Check token that is deemded valid by the relevant protection. There are
   * some exceptions depending on the service; for example, some services will
   * still allow requests bearing the developer's privileged service account
   * credentials without an App Check token. App Check metrics continue to be
   * collected to help you detect issues with your App Check integration and
   * monitor the composition of your callers. While the service is protected by
   * App Check, other applicable protections, such as user authorization,
   * continue to be enforced at the same time. Use caution when choosing to
   * enforce App Check protections. If your users have not updated to a version
   * of your app that meets the requirements of the relevant App Check
   * protection, their app may stop working. App Check metrics can help you
   * decide whether to enforce App Check on your services and resources. If your
   * app has not launched yet, you should enable enforcement as soon as you
   * verify that your App Check implementation is correct, since there are no
   * outdated clients in use. Some services require certain conditions to be met
   * before they will work with App Check, such as requiring you to upgrade to a
   * specific service tier. Until those requirements are met for a service, this
   * `ENFORCED` setting will have no effect and App Check will not work with
   * that service.
   */
  public const ENFORCEMENT_MODE_ENFORCED = 'ENFORCED';
  /**
   * The relevant App Check protection is not enforced for the service or
   * resource, nor are App Check metrics collected. Though the relevant App
   * Check protection is not applied, other applicable protections, such as user
   * authorization, are still enforced. An unconfigured protection is in this
   * mode by default.
   */
  public const REPLAY_PROTECTION_OFF = 'OFF';
  /**
   * The relevant App Check protection is not enforced for the service or
   * resource. App Check metrics are collected to help you decide when to turn
   * on enforcement. These metrics will show the portion of traffic that is
   * deemed invalid by the relevant App Check protection, but that traffic will
   * not be rejected until you turn on enforcement. Though the relevant App
   * Check protection is not enforced, other applicable protections, such as
   * user authorization, are still enforced. Some services require certain
   * conditions to be met before they will work with App Check, such as
   * requiring you to upgrade to a specific service tier. Until those
   * requirements are met for a service, this `UNENFORCED` setting will have no
   * effect and App Check will not work with that service.
   */
  public const REPLAY_PROTECTION_UNENFORCED = 'UNENFORCED';
  /**
   * The relevant App Check protection is enforced for the service or resource.
   * The service or resource will reject any traffic not accompanied by an App
   * Check token that is deemded valid by the relevant protection. There are
   * some exceptions depending on the service; for example, some services will
   * still allow requests bearing the developer's privileged service account
   * credentials without an App Check token. App Check metrics continue to be
   * collected to help you detect issues with your App Check integration and
   * monitor the composition of your callers. While the service is protected by
   * App Check, other applicable protections, such as user authorization,
   * continue to be enforced at the same time. Use caution when choosing to
   * enforce App Check protections. If your users have not updated to a version
   * of your app that meets the requirements of the relevant App Check
   * protection, their app may stop working. App Check metrics can help you
   * decide whether to enforce App Check on your services and resources. If your
   * app has not launched yet, you should enable enforcement as soon as you
   * verify that your App Check implementation is correct, since there are no
   * outdated clients in use. Some services require certain conditions to be met
   * before they will work with App Check, such as requiring you to upgrade to a
   * specific service tier. Until those requirements are met for a service, this
   * `ENFORCED` setting will have no effect and App Check will not work with
   * that service.
   */
  public const REPLAY_PROTECTION_ENFORCED = 'ENFORCED';
  /**
   * Required. The App Check enforcement mode for this service.
   *
   * @var string
   */
  public $enforcementMode;
  /**
   * Optional. This checksum is computed by the server based on the value of
   * other fields, and may be sent on update and delete requests to ensure the
   * client has an up-to-date value before proceeding. This etag is strongly
   * validated as defined by RFC 7232.
   *
   * @var string
   */
  public $etag;
  /**
   * Required. The relative resource name of the service configuration object,
   * in the format: ``` projects/{project_number}/services/{service_id} ``` Note
   * that the `service_id` element must be a supported service ID. Currently,
   * the following service IDs are supported: * `firebasestorage.googleapis.com`
   * (Cloud Storage for Firebase) * `firebasedatabase.googleapis.com` (Firebase
   * Realtime Database) * `firestore.googleapis.com` (Cloud Firestore) *
   * `oauth2.googleapis.com` (Google Identity for iOS)
   *
   * @var string
   */
  public $name;
  /**
   * Optional. The replay protection enforcement mode for this service. Note
   * that this field cannot be set to a level higher than the overall App Check
   * enforcement mode. For example, if the overall App Check enforcement mode is
   * set to `UNENFORCED`, this field cannot be set to `ENFORCED`. In order to
   * enforce replay protection, you must first enforce App Check. An HTTP 400
   * error will be returned in this case. By default, this field is set to
   * `OFF`. Setting this field to `UNENFORCED` or `ENFORCED` is considered
   * opting into replay protection. Once opted in, requests to your protected
   * services may experience higher latency. To opt out of replay protection
   * after opting in, set this field to `OFF`.
   *
   * @var string
   */
  public $replayProtection;
  /**
   * Output only. Timestamp when this service configuration object was most
   * recently updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Required. The App Check enforcement mode for this service.
   *
   * Accepted values: OFF, UNENFORCED, ENFORCED
   *
   * @param self::ENFORCEMENT_MODE_* $enforcementMode
   */
  public function setEnforcementMode($enforcementMode)
  {
    $this->enforcementMode = $enforcementMode;
  }
  /**
   * @return self::ENFORCEMENT_MODE_*
   */
  public function getEnforcementMode()
  {
    return $this->enforcementMode;
  }
  /**
   * Optional. This checksum is computed by the server based on the value of
   * other fields, and may be sent on update and delete requests to ensure the
   * client has an up-to-date value before proceeding. This etag is strongly
   * validated as defined by RFC 7232.
   *
   * @param string $etag
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * Required. The relative resource name of the service configuration object,
   * in the format: ``` projects/{project_number}/services/{service_id} ``` Note
   * that the `service_id` element must be a supported service ID. Currently,
   * the following service IDs are supported: * `firebasestorage.googleapis.com`
   * (Cloud Storage for Firebase) * `firebasedatabase.googleapis.com` (Firebase
   * Realtime Database) * `firestore.googleapis.com` (Cloud Firestore) *
   * `oauth2.googleapis.com` (Google Identity for iOS)
   *
   * @param string $name
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
   * Optional. The replay protection enforcement mode for this service. Note
   * that this field cannot be set to a level higher than the overall App Check
   * enforcement mode. For example, if the overall App Check enforcement mode is
   * set to `UNENFORCED`, this field cannot be set to `ENFORCED`. In order to
   * enforce replay protection, you must first enforce App Check. An HTTP 400
   * error will be returned in this case. By default, this field is set to
   * `OFF`. Setting this field to `UNENFORCED` or `ENFORCED` is considered
   * opting into replay protection. Once opted in, requests to your protected
   * services may experience higher latency. To opt out of replay protection
   * after opting in, set this field to `OFF`.
   *
   * Accepted values: OFF, UNENFORCED, ENFORCED
   *
   * @param self::REPLAY_PROTECTION_* $replayProtection
   */
  public function setReplayProtection($replayProtection)
  {
    $this->replayProtection = $replayProtection;
  }
  /**
   * @return self::REPLAY_PROTECTION_*
   */
  public function getReplayProtection()
  {
    return $this->replayProtection;
  }
  /**
   * Output only. Timestamp when this service configuration object was most
   * recently updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleFirebaseAppcheckV1Service::class, 'Google_Service_Firebaseappcheck_GoogleFirebaseAppcheckV1Service');
