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

namespace Google\Service\Walletobjects\Resource;

use Google\Service\Walletobjects\SetPassUpdateNoticeRequest;
use Google\Service\Walletobjects\SetPassUpdateNoticeResponse;

/**
 * The "privateContent" collection of methods.
 * Typical usage is:
 *  <code>
 *   $walletobjectsService = new Google\Service\Walletobjects(...);
 *   $privateContent = $walletobjectsService->walletobjects_v1_privateContent;
 *  </code>
 */
class WalletobjectsV1PrivateContent extends \Google\Service\Resource
{
  /**
   * Provide Google with information about awaiting private pass update. This will
   * allow Google to provide the update notification to the device that currently
   * holds this pass. (privateContent.setPassUpdateNotice)
   *
   * @param SetPassUpdateNoticeRequest $postBody
   * @param array $optParams Optional parameters.
   * @return SetPassUpdateNoticeResponse
   * @throws \Google\Service\Exception
   */
  public function setPassUpdateNotice(SetPassUpdateNoticeRequest $postBody, $optParams = [])
  {
    $params = ['postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('setPassUpdateNotice', [$params], SetPassUpdateNoticeResponse::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(WalletobjectsV1PrivateContent::class, 'Google_Service_Walletobjects_Resource_WalletobjectsV1PrivateContent');
