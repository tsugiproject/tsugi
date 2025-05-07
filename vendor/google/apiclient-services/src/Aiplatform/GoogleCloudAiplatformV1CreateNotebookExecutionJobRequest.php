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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1CreateNotebookExecutionJobRequest extends \Google\Model
{
  protected $notebookExecutionJobType = GoogleCloudAiplatformV1NotebookExecutionJob::class;
  protected $notebookExecutionJobDataType = '';
  /**
   * @var string
   */
  public $notebookExecutionJobId;
  /**
   * @var string
   */
  public $parent;

  /**
   * @param GoogleCloudAiplatformV1NotebookExecutionJob
   */
  public function setNotebookExecutionJob(GoogleCloudAiplatformV1NotebookExecutionJob $notebookExecutionJob)
  {
    $this->notebookExecutionJob = $notebookExecutionJob;
  }
  /**
   * @return GoogleCloudAiplatformV1NotebookExecutionJob
   */
  public function getNotebookExecutionJob()
  {
    return $this->notebookExecutionJob;
  }
  /**
   * @param string
   */
  public function setNotebookExecutionJobId($notebookExecutionJobId)
  {
    $this->notebookExecutionJobId = $notebookExecutionJobId;
  }
  /**
   * @return string
   */
  public function getNotebookExecutionJobId()
  {
    return $this->notebookExecutionJobId;
  }
  /**
   * @param string
   */
  public function setParent($parent)
  {
    $this->parent = $parent;
  }
  /**
   * @return string
   */
  public function getParent()
  {
    return $this->parent;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1CreateNotebookExecutionJobRequest::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1CreateNotebookExecutionJobRequest');
