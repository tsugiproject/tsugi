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

namespace Google\Service\PaymentsResellerSubscription;

class SuspendSubscriptionRequest extends \Google\Model
{
  /**
   * The default value. Suspend the subscription, and the subscription will stay
   * suspended indefinitely.
   */
  public const SUSPEND_MODE_SUSPEND_MODE_UNSPECIFIED = 'SUSPEND_MODE_UNSPECIFIED';
  /**
   * Suspend the subscription, and the subscription will be auto cancelled after
   * the grace period. Contract terms dictate how long the grace period is.
   */
  public const SUSPEND_MODE_SUSPEND_MODE_CANCEL_AFTER_GRACE_PERIOD = 'SUSPEND_MODE_CANCEL_AFTER_GRACE_PERIOD';
  /**
   * Suspend the subscription, and the subscription will be auto cancelled after
   * the retention period. Contract terms dictate how long the retention period
   * is.
   */
  public const SUSPEND_MODE_SUSPEND_MODE_CANCEL_AFTER_RETENTION_PERIOD = 'SUSPEND_MODE_CANCEL_AFTER_RETENTION_PERIOD';
  /**
   * Optional. The mode to suspend the subscription. It's required for partners
   * to specify the suspend mode, whether suspend immediately and indefinitely,
   * or cancel the subscription after grace_period_millis or
   * auto_cancel_duration_millis if it's not resumed.
   *
   * @var string
   */
  public $suspendMode;

  /**
   * Optional. The mode to suspend the subscription. It's required for partners
   * to specify the suspend mode, whether suspend immediately and indefinitely,
   * or cancel the subscription after grace_period_millis or
   * auto_cancel_duration_millis if it's not resumed.
   *
   * Accepted values: SUSPEND_MODE_UNSPECIFIED,
   * SUSPEND_MODE_CANCEL_AFTER_GRACE_PERIOD,
   * SUSPEND_MODE_CANCEL_AFTER_RETENTION_PERIOD
   *
   * @param self::SUSPEND_MODE_* $suspendMode
   */
  public function setSuspendMode($suspendMode)
  {
    $this->suspendMode = $suspendMode;
  }
  /**
   * @return self::SUSPEND_MODE_*
   */
  public function getSuspendMode()
  {
    return $this->suspendMode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(SuspendSubscriptionRequest::class, 'Google_Service_PaymentsResellerSubscription_SuspendSubscriptionRequest');
