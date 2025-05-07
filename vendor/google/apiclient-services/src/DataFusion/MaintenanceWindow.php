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

namespace Google\Service\DataFusion;

class MaintenanceWindow extends \Google\Model
{
  protected $recurringTimeWindowType = RecurringTimeWindow::class;
  protected $recurringTimeWindowDataType = '';

  /**
   * @param RecurringTimeWindow
   */
  public function setRecurringTimeWindow(RecurringTimeWindow $recurringTimeWindow)
  {
    $this->recurringTimeWindow = $recurringTimeWindow;
  }
  /**
   * @return RecurringTimeWindow
   */
  public function getRecurringTimeWindow()
  {
    return $this->recurringTimeWindow;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(MaintenanceWindow::class, 'Google_Service_DataFusion_MaintenanceWindow');
