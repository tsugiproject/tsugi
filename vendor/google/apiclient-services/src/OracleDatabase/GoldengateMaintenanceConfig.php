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

namespace Google\Service\OracleDatabase;

class GoldengateMaintenanceConfig extends \Google\Model
{
  /**
   * Optional. Defines auto upgrade period for bundle releases. Manually
   * configured period cannot be longer than service defined period for bundle
   * releases. This period must be shorter or equal to major release upgrade
   * period. Not passing this field during create will equate to using the
   * service default.
   *
   * @var int
   */
  public $bundleReleaseUpgradePeriodDays;
  /**
   * Optional. Defines auto upgrade period for interim releases. This period
   * must be shorter or equal to bundle release upgrade period.
   *
   * @var int
   */
  public $interimReleaseUpgradePeriodDays;
  /**
   * Optional. By default auto upgrade for interim releases are not enabled. If
   * auto-upgrade is enabled for interim release, you have to specify
   * interim_release_upgrade_period_days too.
   *
   * @var bool
   */
  public $isInterimReleaseAutoUpgradeEnabled;
  /**
   * Optional. Defines auto upgrade period for major releases. Manually
   * configured period cannot be longer than service defined period for major
   * releases. Not passing this field during create will equate to using the
   * service default.
   *
   * @var int
   */
  public $majorReleaseUpgradePeriodDays;
  /**
   * Optional. Defines auto upgrade period for releases with security fix.
   * Manually configured period cannot be longer than service defined period for
   * security releases. Not passing this field during create will equate to
   * using the service default.
   *
   * @var int
   */
  public $securityPatchUpgradePeriodDays;

  /**
   * Optional. Defines auto upgrade period for bundle releases. Manually
   * configured period cannot be longer than service defined period for bundle
   * releases. This period must be shorter or equal to major release upgrade
   * period. Not passing this field during create will equate to using the
   * service default.
   *
   * @param int $bundleReleaseUpgradePeriodDays
   */
  public function setBundleReleaseUpgradePeriodDays($bundleReleaseUpgradePeriodDays)
  {
    $this->bundleReleaseUpgradePeriodDays = $bundleReleaseUpgradePeriodDays;
  }
  /**
   * @return int
   */
  public function getBundleReleaseUpgradePeriodDays()
  {
    return $this->bundleReleaseUpgradePeriodDays;
  }
  /**
   * Optional. Defines auto upgrade period for interim releases. This period
   * must be shorter or equal to bundle release upgrade period.
   *
   * @param int $interimReleaseUpgradePeriodDays
   */
  public function setInterimReleaseUpgradePeriodDays($interimReleaseUpgradePeriodDays)
  {
    $this->interimReleaseUpgradePeriodDays = $interimReleaseUpgradePeriodDays;
  }
  /**
   * @return int
   */
  public function getInterimReleaseUpgradePeriodDays()
  {
    return $this->interimReleaseUpgradePeriodDays;
  }
  /**
   * Optional. By default auto upgrade for interim releases are not enabled. If
   * auto-upgrade is enabled for interim release, you have to specify
   * interim_release_upgrade_period_days too.
   *
   * @param bool $isInterimReleaseAutoUpgradeEnabled
   */
  public function setIsInterimReleaseAutoUpgradeEnabled($isInterimReleaseAutoUpgradeEnabled)
  {
    $this->isInterimReleaseAutoUpgradeEnabled = $isInterimReleaseAutoUpgradeEnabled;
  }
  /**
   * @return bool
   */
  public function getIsInterimReleaseAutoUpgradeEnabled()
  {
    return $this->isInterimReleaseAutoUpgradeEnabled;
  }
  /**
   * Optional. Defines auto upgrade period for major releases. Manually
   * configured period cannot be longer than service defined period for major
   * releases. Not passing this field during create will equate to using the
   * service default.
   *
   * @param int $majorReleaseUpgradePeriodDays
   */
  public function setMajorReleaseUpgradePeriodDays($majorReleaseUpgradePeriodDays)
  {
    $this->majorReleaseUpgradePeriodDays = $majorReleaseUpgradePeriodDays;
  }
  /**
   * @return int
   */
  public function getMajorReleaseUpgradePeriodDays()
  {
    return $this->majorReleaseUpgradePeriodDays;
  }
  /**
   * Optional. Defines auto upgrade period for releases with security fix.
   * Manually configured period cannot be longer than service defined period for
   * security releases. Not passing this field during create will equate to
   * using the service default.
   *
   * @param int $securityPatchUpgradePeriodDays
   */
  public function setSecurityPatchUpgradePeriodDays($securityPatchUpgradePeriodDays)
  {
    $this->securityPatchUpgradePeriodDays = $securityPatchUpgradePeriodDays;
  }
  /**
   * @return int
   */
  public function getSecurityPatchUpgradePeriodDays()
  {
    return $this->securityPatchUpgradePeriodDays;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateMaintenanceConfig::class, 'Google_Service_OracleDatabase_GoldengateMaintenanceConfig');
