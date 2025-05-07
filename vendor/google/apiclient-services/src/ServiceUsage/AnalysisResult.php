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

namespace Google\Service\ServiceUsage;

class AnalysisResult extends \Google\Collection
{
  protected $collection_key = 'warnings';
  protected $blockersType = Impact::class;
  protected $blockersDataType = 'array';
  protected $warningsType = Impact::class;
  protected $warningsDataType = 'array';

  /**
   * @param Impact[]
   */
  public function setBlockers($blockers)
  {
    $this->blockers = $blockers;
  }
  /**
   * @return Impact[]
   */
  public function getBlockers()
  {
    return $this->blockers;
  }
  /**
   * @param Impact[]
   */
  public function setWarnings($warnings)
  {
    $this->warnings = $warnings;
  }
  /**
   * @return Impact[]
   */
  public function getWarnings()
  {
    return $this->warnings;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(AnalysisResult::class, 'Google_Service_ServiceUsage_AnalysisResult');
