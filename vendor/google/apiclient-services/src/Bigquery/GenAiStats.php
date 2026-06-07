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

namespace Google\Service\Bigquery;

class GenAiStats extends \Google\Collection
{
  protected $collection_key = 'functionStats';
  protected $errorStatsType = GenAiErrorStats::class;
  protected $errorStatsDataType = '';
  protected $functionStatsType = GenAiFunctionStats::class;
  protected $functionStatsDataType = 'array';

  /**
   * Job level error stats across all GenAi functions
   *
   * @param GenAiErrorStats $errorStats
   */
  public function setErrorStats(GenAiErrorStats $errorStats)
  {
    $this->errorStats = $errorStats;
  }
  /**
   * @return GenAiErrorStats
   */
  public function getErrorStats()
  {
    return $this->errorStats;
  }
  /**
   * Function level stats for GenAi Functions. See
   * https://docs.cloud.google.com/bigquery/docs/generative-ai-overview
   *
   * @param GenAiFunctionStats[] $functionStats
   */
  public function setFunctionStats($functionStats)
  {
    $this->functionStats = $functionStats;
  }
  /**
   * @return GenAiFunctionStats[]
   */
  public function getFunctionStats()
  {
    return $this->functionStats;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GenAiStats::class, 'Google_Service_Bigquery_GenAiStats');
