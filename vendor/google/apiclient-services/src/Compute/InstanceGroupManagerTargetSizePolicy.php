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

class InstanceGroupManagerTargetSizePolicy extends \Google\Model
{
  /**
   * The mode in which the MIG creates VMs all at once. In this mode, if the MIG
   * is unable to create even one VM, the MIG waits until all VMs can be created
   * at the same time.
   */
  public const MODE_BULK = 'BULK';
  /**
   * The mode in which the MIG creates VMs individually. In this mode, if the
   * MIG is unable to create a VM, the MIG will continue to create the other VMs
   * in the group. This is the default mode.
   */
  public const MODE_INDIVIDUAL = 'INDIVIDUAL';
  /**
   * If mode is unspecified, MIG will behave as in the default `INDIVIDUAL`
   * mode.
   */
  public const MODE_UNSPECIFIED_MODE = 'UNSPECIFIED_MODE';
  /**
   * The mode of target size policy based on which the MIG creates its VMs
   * individually or all at once.
   *
   * @var string
   */
  public $mode;

  /**
   * The mode of target size policy based on which the MIG creates its VMs
   * individually or all at once.
   *
   * Accepted values: BULK, INDIVIDUAL, UNSPECIFIED_MODE
   *
   * @param self::MODE_* $mode
   */
  public function setMode($mode)
  {
    $this->mode = $mode;
  }
  /**
   * @return self::MODE_*
   */
  public function getMode()
  {
    return $this->mode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(InstanceGroupManagerTargetSizePolicy::class, 'Google_Service_Compute_InstanceGroupManagerTargetSizePolicy');
