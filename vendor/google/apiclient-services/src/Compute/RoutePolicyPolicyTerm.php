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

class RoutePolicyPolicyTerm extends \Google\Collection
{
  protected $collection_key = 'actions';
  protected $actionsType = Expr::class;
  protected $actionsDataType = 'array';
  protected $matchType = Expr::class;
  protected $matchDataType = '';
  /**
   * @var int
   */
  public $priority;

  /**
   * @param Expr[]
   */
  public function setActions($actions)
  {
    $this->actions = $actions;
  }
  /**
   * @return Expr[]
   */
  public function getActions()
  {
    return $this->actions;
  }
  /**
   * @param Expr
   */
  public function setMatch(Expr $match)
  {
    $this->match = $match;
  }
  /**
   * @return Expr
   */
  public function getMatch()
  {
    return $this->match;
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RoutePolicyPolicyTerm::class, 'Google_Service_Compute_RoutePolicyPolicyTerm');
