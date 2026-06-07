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

class GoldengateConnectionAssignmentProperties extends \Google\Model
{
  /**
   * Lifecycle state is unspecified.
   */
  public const STATE_STATE_UNSPECIFIED = 'STATE_UNSPECIFIED';
  /**
   * Connection assignment is being created.
   */
  public const STATE_CREATING = 'CREATING';
  /**
   * Connection assignment is active.
   */
  public const STATE_ACTIVE = 'ACTIVE';
  /**
   * Connection assignment failed.
   */
  public const STATE_FAILED = 'FAILED';
  /**
   * Connection assignment is being updated.
   */
  public const STATE_UPDATING = 'UPDATING';
  /**
   * Connection assignment is being deleted.
   */
  public const STATE_DELETING = 'DELETING';
  /**
   * Output only. Credential store alias.
   *
   * @var string
   */
  public $alias;
  /**
   * Required. The GoldengateConnection resource to be assigned. Format: project
   * s/{project}/locations/{location}/goldengateConnections/{goldengate_connecti
   * on}
   *
   * @var string
   */
  public $goldengateConnection;
  /**
   * Required. The GoldenGateDeployment to assign the connection to. Format: pro
   * jects/{project}/locations/{location}/goldengateDeployments/{goldengate_depl
   * oyment}
   *
   * @var string
   */
  public $goldengateDeployment;
  /**
   * Output only. The [OCID](https://docs.cloud.oracle.com/Content/General/Conce
   * pts/identifiers.htm) of the connection assignment being referenced.
   *
   * @var string
   */
  public $ocid;
  /**
   * Output only. The lifecycle state of the connection assignment.
   *
   * @var string
   */
  public $state;

  /**
   * Output only. Credential store alias.
   *
   * @param string $alias
   */
  public function setAlias($alias)
  {
    $this->alias = $alias;
  }
  /**
   * @return string
   */
  public function getAlias()
  {
    return $this->alias;
  }
  /**
   * Required. The GoldengateConnection resource to be assigned. Format: project
   * s/{project}/locations/{location}/goldengateConnections/{goldengate_connecti
   * on}
   *
   * @param string $goldengateConnection
   */
  public function setGoldengateConnection($goldengateConnection)
  {
    $this->goldengateConnection = $goldengateConnection;
  }
  /**
   * @return string
   */
  public function getGoldengateConnection()
  {
    return $this->goldengateConnection;
  }
  /**
   * Required. The GoldenGateDeployment to assign the connection to. Format: pro
   * jects/{project}/locations/{location}/goldengateDeployments/{goldengate_depl
   * oyment}
   *
   * @param string $goldengateDeployment
   */
  public function setGoldengateDeployment($goldengateDeployment)
  {
    $this->goldengateDeployment = $goldengateDeployment;
  }
  /**
   * @return string
   */
  public function getGoldengateDeployment()
  {
    return $this->goldengateDeployment;
  }
  /**
   * Output only. The [OCID](https://docs.cloud.oracle.com/Content/General/Conce
   * pts/identifiers.htm) of the connection assignment being referenced.
   *
   * @param string $ocid
   */
  public function setOcid($ocid)
  {
    $this->ocid = $ocid;
  }
  /**
   * @return string
   */
  public function getOcid()
  {
    return $this->ocid;
  }
  /**
   * Output only. The lifecycle state of the connection assignment.
   *
   * Accepted values: STATE_UNSPECIFIED, CREATING, ACTIVE, FAILED, UPDATING,
   * DELETING
   *
   * @param self::STATE_* $state
   */
  public function setState($state)
  {
    $this->state = $state;
  }
  /**
   * @return self::STATE_*
   */
  public function getState()
  {
    return $this->state;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateConnectionAssignmentProperties::class, 'Google_Service_OracleDatabase_GoldengateConnectionAssignmentProperties');
