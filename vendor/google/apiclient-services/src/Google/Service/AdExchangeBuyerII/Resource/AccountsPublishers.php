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

/**
 * The "publishers" collection of methods.
 * Typical usage is:
 *  <code>
 *   $adexchangebuyer2Service = new Google_Service_AdExchangeBuyerII(...);
 *   $publishers = $adexchangebuyer2Service->publishers;
 *  </code>
 */
class Google_Service_AdExchangeBuyerII_Resource_AccountsPublishers extends Google_Service_Resource
{
  /**
   * Lists publishers that had recent inventory matches with the requesting buyer.
   * (publishers.listAccountsPublishers)
   *
   * @param string $accountId Account ID of the requesting buyer.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string environment Optional environment (WEB, APP) for which to
   * return publishers. If specified, response will only include publishers that
   * had recent inventory matches with the requesting buyer on the specified
   * platform.
   * @return Google_Service_AdExchangeBuyerII_ListPublishersResponse
   */
  public function listAccountsPublishers($accountId, $optParams = array())
  {
    $params = array('accountId' => $accountId);
    $params = array_merge($params, $optParams);
    return $this->call('list', array($params), "Google_Service_AdExchangeBuyerII_ListPublishersResponse");
  }
}
