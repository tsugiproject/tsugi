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

namespace Google\Service\WorkloadManager;

class TenantProjectProxy extends \Google\Collection
{
  protected $collection_key = 'projectNumbers';
  /**
   * @var string[]
   */
  public $projectNumbers;

  /**
   * @param string[]
   */
  public function setProjectNumbers($projectNumbers)
  {
    $this->projectNumbers = $projectNumbers;
  }
  /**
   * @return string[]
   */
  public function getProjectNumbers()
  {
    return $this->projectNumbers;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(TenantProjectProxy::class, 'Google_Service_WorkloadManager_TenantProjectProxy');
