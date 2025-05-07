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

namespace Google\Service\CloudSearch;

class RewrittenQuery extends \Google\Model
{
  /**
   * @var string
   */
  public $rewrittenQuery;
  public $score;
  /**
   * @var string
   */
  public $sortBy;

  /**
   * @param string
   */
  public function setRewrittenQuery($rewrittenQuery)
  {
    $this->rewrittenQuery = $rewrittenQuery;
  }
  /**
   * @return string
   */
  public function getRewrittenQuery()
  {
    return $this->rewrittenQuery;
  }
  public function setScore($score)
  {
    $this->score = $score;
  }
  public function getScore()
  {
    return $this->score;
  }
  /**
   * @param string
   */
  public function setSortBy($sortBy)
  {
    $this->sortBy = $sortBy;
  }
  /**
   * @return string
   */
  public function getSortBy()
  {
    return $this->sortBy;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RewrittenQuery::class, 'Google_Service_CloudSearch_RewrittenQuery');
