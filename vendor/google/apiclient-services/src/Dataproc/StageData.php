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

namespace Google\Service\Dataproc;

class StageData extends \Google\Collection
{
  protected $collection_key = 'rddIds';
  protected $accumulatorUpdatesType = AccumulableInfo::class;
  protected $accumulatorUpdatesDataType = 'array';
  /**
   * @var string
   */
  public $completionTime;
  /**
   * @var string
   */
  public $description;
  /**
   * @var string
   */
  public $details;
  protected $executorMetricsDistributionsType = ExecutorMetricsDistributions::class;
  protected $executorMetricsDistributionsDataType = '';
  protected $executorSummaryType = ExecutorStageSummary::class;
  protected $executorSummaryDataType = 'map';
  /**
   * @var string
   */
  public $failureReason;
  /**
   * @var string
   */
  public $firstTaskLaunchedTime;
  /**
   * @var bool
   */
  public $isShufflePushEnabled;
  /**
   * @var string[]
   */
  public $jobIds;
  /**
   * @var int[]
   */
  public $killedTasksSummary;
  /**
   * @var string[]
   */
  public $locality;
  /**
   * @var string
   */
  public $name;
  /**
   * @var int
   */
  public $numActiveTasks;
  /**
   * @var int
   */
  public $numCompleteTasks;
  /**
   * @var int
   */
  public $numCompletedIndices;
  /**
   * @var int
   */
  public $numFailedTasks;
  /**
   * @var int
   */
  public $numKilledTasks;
  /**
   * @var int
   */
  public $numTasks;
  /**
   * @var string[]
   */
  public $parentStageIds;
  protected $peakExecutorMetricsType = ExecutorMetrics::class;
  protected $peakExecutorMetricsDataType = '';
  /**
   * @var string[]
   */
  public $rddIds;
  /**
   * @var int
   */
  public $resourceProfileId;
  /**
   * @var string
   */
  public $schedulingPool;
  /**
   * @var int
   */
  public $shuffleMergersCount;
  protected $speculationSummaryType = SpeculationStageSummary::class;
  protected $speculationSummaryDataType = '';
  /**
   * @var int
   */
  public $stageAttemptId;
  /**
   * @var string
   */
  public $stageId;
  protected $stageMetricsType = StageMetrics::class;
  protected $stageMetricsDataType = '';
  /**
   * @var string
   */
  public $status;
  /**
   * @var string
   */
  public $submissionTime;
  protected $taskQuantileMetricsType = TaskQuantileMetrics::class;
  protected $taskQuantileMetricsDataType = '';
  protected $tasksType = TaskData::class;
  protected $tasksDataType = 'map';

  /**
   * @param AccumulableInfo[]
   */
  public function setAccumulatorUpdates($accumulatorUpdates)
  {
    $this->accumulatorUpdates = $accumulatorUpdates;
  }
  /**
   * @return AccumulableInfo[]
   */
  public function getAccumulatorUpdates()
  {
    return $this->accumulatorUpdates;
  }
  /**
   * @param string
   */
  public function setCompletionTime($completionTime)
  {
    $this->completionTime = $completionTime;
  }
  /**
   * @return string
   */
  public function getCompletionTime()
  {
    return $this->completionTime;
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
   * @param string
   */
  public function setDetails($details)
  {
    $this->details = $details;
  }
  /**
   * @return string
   */
  public function getDetails()
  {
    return $this->details;
  }
  /**
   * @param ExecutorMetricsDistributions
   */
  public function setExecutorMetricsDistributions(ExecutorMetricsDistributions $executorMetricsDistributions)
  {
    $this->executorMetricsDistributions = $executorMetricsDistributions;
  }
  /**
   * @return ExecutorMetricsDistributions
   */
  public function getExecutorMetricsDistributions()
  {
    return $this->executorMetricsDistributions;
  }
  /**
   * @param ExecutorStageSummary[]
   */
  public function setExecutorSummary($executorSummary)
  {
    $this->executorSummary = $executorSummary;
  }
  /**
   * @return ExecutorStageSummary[]
   */
  public function getExecutorSummary()
  {
    return $this->executorSummary;
  }
  /**
   * @param string
   */
  public function setFailureReason($failureReason)
  {
    $this->failureReason = $failureReason;
  }
  /**
   * @return string
   */
  public function getFailureReason()
  {
    return $this->failureReason;
  }
  /**
   * @param string
   */
  public function setFirstTaskLaunchedTime($firstTaskLaunchedTime)
  {
    $this->firstTaskLaunchedTime = $firstTaskLaunchedTime;
  }
  /**
   * @return string
   */
  public function getFirstTaskLaunchedTime()
  {
    return $this->firstTaskLaunchedTime;
  }
  /**
   * @param bool
   */
  public function setIsShufflePushEnabled($isShufflePushEnabled)
  {
    $this->isShufflePushEnabled = $isShufflePushEnabled;
  }
  /**
   * @return bool
   */
  public function getIsShufflePushEnabled()
  {
    return $this->isShufflePushEnabled;
  }
  /**
   * @param string[]
   */
  public function setJobIds($jobIds)
  {
    $this->jobIds = $jobIds;
  }
  /**
   * @return string[]
   */
  public function getJobIds()
  {
    return $this->jobIds;
  }
  /**
   * @param int[]
   */
  public function setKilledTasksSummary($killedTasksSummary)
  {
    $this->killedTasksSummary = $killedTasksSummary;
  }
  /**
   * @return int[]
   */
  public function getKilledTasksSummary()
  {
    return $this->killedTasksSummary;
  }
  /**
   * @param string[]
   */
  public function setLocality($locality)
  {
    $this->locality = $locality;
  }
  /**
   * @return string[]
   */
  public function getLocality()
  {
    return $this->locality;
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
   * @param int
   */
  public function setNumActiveTasks($numActiveTasks)
  {
    $this->numActiveTasks = $numActiveTasks;
  }
  /**
   * @return int
   */
  public function getNumActiveTasks()
  {
    return $this->numActiveTasks;
  }
  /**
   * @param int
   */
  public function setNumCompleteTasks($numCompleteTasks)
  {
    $this->numCompleteTasks = $numCompleteTasks;
  }
  /**
   * @return int
   */
  public function getNumCompleteTasks()
  {
    return $this->numCompleteTasks;
  }
  /**
   * @param int
   */
  public function setNumCompletedIndices($numCompletedIndices)
  {
    $this->numCompletedIndices = $numCompletedIndices;
  }
  /**
   * @return int
   */
  public function getNumCompletedIndices()
  {
    return $this->numCompletedIndices;
  }
  /**
   * @param int
   */
  public function setNumFailedTasks($numFailedTasks)
  {
    $this->numFailedTasks = $numFailedTasks;
  }
  /**
   * @return int
   */
  public function getNumFailedTasks()
  {
    return $this->numFailedTasks;
  }
  /**
   * @param int
   */
  public function setNumKilledTasks($numKilledTasks)
  {
    $this->numKilledTasks = $numKilledTasks;
  }
  /**
   * @return int
   */
  public function getNumKilledTasks()
  {
    return $this->numKilledTasks;
  }
  /**
   * @param int
   */
  public function setNumTasks($numTasks)
  {
    $this->numTasks = $numTasks;
  }
  /**
   * @return int
   */
  public function getNumTasks()
  {
    return $this->numTasks;
  }
  /**
   * @param string[]
   */
  public function setParentStageIds($parentStageIds)
  {
    $this->parentStageIds = $parentStageIds;
  }
  /**
   * @return string[]
   */
  public function getParentStageIds()
  {
    return $this->parentStageIds;
  }
  /**
   * @param ExecutorMetrics
   */
  public function setPeakExecutorMetrics(ExecutorMetrics $peakExecutorMetrics)
  {
    $this->peakExecutorMetrics = $peakExecutorMetrics;
  }
  /**
   * @return ExecutorMetrics
   */
  public function getPeakExecutorMetrics()
  {
    return $this->peakExecutorMetrics;
  }
  /**
   * @param string[]
   */
  public function setRddIds($rddIds)
  {
    $this->rddIds = $rddIds;
  }
  /**
   * @return string[]
   */
  public function getRddIds()
  {
    return $this->rddIds;
  }
  /**
   * @param int
   */
  public function setResourceProfileId($resourceProfileId)
  {
    $this->resourceProfileId = $resourceProfileId;
  }
  /**
   * @return int
   */
  public function getResourceProfileId()
  {
    return $this->resourceProfileId;
  }
  /**
   * @param string
   */
  public function setSchedulingPool($schedulingPool)
  {
    $this->schedulingPool = $schedulingPool;
  }
  /**
   * @return string
   */
  public function getSchedulingPool()
  {
    return $this->schedulingPool;
  }
  /**
   * @param int
   */
  public function setShuffleMergersCount($shuffleMergersCount)
  {
    $this->shuffleMergersCount = $shuffleMergersCount;
  }
  /**
   * @return int
   */
  public function getShuffleMergersCount()
  {
    return $this->shuffleMergersCount;
  }
  /**
   * @param SpeculationStageSummary
   */
  public function setSpeculationSummary(SpeculationStageSummary $speculationSummary)
  {
    $this->speculationSummary = $speculationSummary;
  }
  /**
   * @return SpeculationStageSummary
   */
  public function getSpeculationSummary()
  {
    return $this->speculationSummary;
  }
  /**
   * @param int
   */
  public function setStageAttemptId($stageAttemptId)
  {
    $this->stageAttemptId = $stageAttemptId;
  }
  /**
   * @return int
   */
  public function getStageAttemptId()
  {
    return $this->stageAttemptId;
  }
  /**
   * @param string
   */
  public function setStageId($stageId)
  {
    $this->stageId = $stageId;
  }
  /**
   * @return string
   */
  public function getStageId()
  {
    return $this->stageId;
  }
  /**
   * @param StageMetrics
   */
  public function setStageMetrics(StageMetrics $stageMetrics)
  {
    $this->stageMetrics = $stageMetrics;
  }
  /**
   * @return StageMetrics
   */
  public function getStageMetrics()
  {
    return $this->stageMetrics;
  }
  /**
   * @param string
   */
  public function setStatus($status)
  {
    $this->status = $status;
  }
  /**
   * @return string
   */
  public function getStatus()
  {
    return $this->status;
  }
  /**
   * @param string
   */
  public function setSubmissionTime($submissionTime)
  {
    $this->submissionTime = $submissionTime;
  }
  /**
   * @return string
   */
  public function getSubmissionTime()
  {
    return $this->submissionTime;
  }
  /**
   * @param TaskQuantileMetrics
   */
  public function setTaskQuantileMetrics(TaskQuantileMetrics $taskQuantileMetrics)
  {
    $this->taskQuantileMetrics = $taskQuantileMetrics;
  }
  /**
   * @return TaskQuantileMetrics
   */
  public function getTaskQuantileMetrics()
  {
    return $this->taskQuantileMetrics;
  }
  /**
   * @param TaskData[]
   */
  public function setTasks($tasks)
  {
    $this->tasks = $tasks;
  }
  /**
   * @return TaskData[]
   */
  public function getTasks()
  {
    return $this->tasks;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(StageData::class, 'Google_Service_Dataproc_StageData');
