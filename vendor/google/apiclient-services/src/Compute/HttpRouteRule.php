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

namespace Google\Service\Compute;

class HttpRouteRule extends \Google\Collection
{
  protected $collection_key = 'matchRules';
  protected $customErrorResponsePolicyType = CustomErrorResponsePolicy::class;
  protected $customErrorResponsePolicyDataType = '';
  /**
   * @var string
   */
  public $description;
  protected $headerActionType = HttpHeaderAction::class;
  protected $headerActionDataType = '';
  protected $matchRulesType = HttpRouteRuleMatch::class;
  protected $matchRulesDataType = 'array';
  /**
   * @var int
   */
  public $priority;
  protected $routeActionType = HttpRouteAction::class;
  protected $routeActionDataType = '';
  /**
   * @var string
   */
  public $service;
  protected $urlRedirectType = HttpRedirectAction::class;
  protected $urlRedirectDataType = '';

  /**
   * @param CustomErrorResponsePolicy
   */
  public function setCustomErrorResponsePolicy(CustomErrorResponsePolicy $customErrorResponsePolicy)
  {
    $this->customErrorResponsePolicy = $customErrorResponsePolicy;
  }
  /**
   * @return CustomErrorResponsePolicy
   */
  public function getCustomErrorResponsePolicy()
  {
    return $this->customErrorResponsePolicy;
  }
  /**
   * @param string
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * @param HttpHeaderAction
   */
  public function setHeaderAction(HttpHeaderAction $headerAction)
  {
    $this->headerAction = $headerAction;
  }
  /**
   * @return HttpHeaderAction
   */
  public function getHeaderAction()
  {
    return $this->headerAction;
  }
  /**
   * @param HttpRouteRuleMatch[]
   */
  public function setMatchRules($matchRules)
  {
    $this->matchRules = $matchRules;
  }
  /**
   * @return HttpRouteRuleMatch[]
   */
  public function getMatchRules()
  {
    return $this->matchRules;
  }
  /**
   * @param int
   */
  public function setPriority($priority)
  {
    $this->priority = $priority;
  }
  /**
   * @return int
   */
  public function getPriority()
  {
    return $this->priority;
  }
  /**
   * @param HttpRouteAction
   */
  public function setRouteAction(HttpRouteAction $routeAction)
  {
    $this->routeAction = $routeAction;
  }
  /**
   * @return HttpRouteAction
   */
  public function getRouteAction()
  {
    return $this->routeAction;
  }
  /**
   * @param string
   */
  public function setService($service)
  {
    $this->service = $service;
  }
  /**
   * @return string
   */
  public function getService()
  {
    return $this->service;
  }
  /**
   * @param HttpRedirectAction
   */
  public function setUrlRedirect(HttpRedirectAction $urlRedirect)
  {
    $this->urlRedirect = $urlRedirect;
  }
  /**
   * @return HttpRedirectAction
   */
  public function getUrlRedirect()
  {
    return $this->urlRedirect;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HttpRouteRule::class, 'Google_Service_Compute_HttpRouteRule');
