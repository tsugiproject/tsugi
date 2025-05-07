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

namespace Google\Service\BigQueryReservation;

class Assignment extends \Google\Model
{
  /**
   * @var string
   */
  public $assignee;
  /**
   * @var bool
   */
  public $enableGeminiInBigquery;
  /**
   * @var string
   */
  public $jobType;
  /**
   * @var string
   */
  public $name;
  /**
   * @var string
   */
  public $state;

  /**
   * @param string
   */
  public function setAssignee($assignee)
  {
    $this->assignee = $assignee;
  }
  /**
   * @return string
   */
  public function getAssignee()
  {
    return $this->assignee;
  }
  /**
   * @param bool
   */
  public function setEnableGeminiInBigquery($enableGeminiInBigquery)
  {
    $this->enableGeminiInBigquery = $enableGeminiInBigquery;
  }
  /**
   * @return bool
   */
  public function getEnableGeminiInBigquery()
  {
    return $this->enableGeminiInBigquery;
  }
  /**
   * @param string
   */
  public function setJobType($jobType)
  {
    $this->jobType = $jobType;
  }
  /**
   * @return string
   */
  public function getJobType()
  {
    return $this->jobType;
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
   * @param string
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return string
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Assignment::class, 'Google_Service_BigQueryReservation_Assignment');
