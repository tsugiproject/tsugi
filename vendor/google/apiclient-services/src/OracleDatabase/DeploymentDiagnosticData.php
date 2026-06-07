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

class DeploymentDiagnosticData extends \Google\Model
{
  /**
   * The diagnostic state is unspecified.
   */
  public const DIAGNOSTIC_STATE_DIAGNOSTIC_STATE_UNSPECIFIED = 'DIAGNOSTIC_STATE_UNSPECIFIED';
  /**
   * The diagnostic is in progress.
   */
  public const DIAGNOSTIC_STATE_IN_PROGRESS = 'IN_PROGRESS';
  /**
   * The diagnostic completed successfully.
   */
  public const DIAGNOSTIC_STATE_SUCCEEDED = 'SUCCEEDED';
  /**
   * The diagnostic failed.
   */
  public const DIAGNOSTIC_STATE_FAILED = 'FAILED';
  /**
   * Output only. The bucket name.
   *
   * @var string
   */
  public $bucket;
  /**
   * Output only. The time diagnostic end.
   *
   * @var string
   */
  public $diagnosticEndTime;
  /**
   * Output only. The time diagnostic start.
   *
   * @var string
   */
  public $diagnosticStartTime;
  /**
   * Output only. The diagnostic state.
   *
   * @var string
   */
  public $diagnosticState;
  /**
   * Output only. The namespace name.
   *
   * @var string
   */
  public $namespace;
  /**
   * Output only. The object name.
   *
   * @var string
   */
  public $object;

  /**
   * Output only. The bucket name.
   *
   * @param string $bucket
   */
  public function setBucket($bucket)
  {
    $this->bucket = $bucket;
  }
  /**
   * @return string
   */
  public function getBucket()
  {
    return $this->bucket;
  }
  /**
   * Output only. The time diagnostic end.
   *
   * @param string $diagnosticEndTime
   */
  public function setDiagnosticEndTime($diagnosticEndTime)
  {
    $this->diagnosticEndTime = $diagnosticEndTime;
  }
  /**
   * @return string
   */
  public function getDiagnosticEndTime()
  {
    return $this->diagnosticEndTime;
  }
  /**
   * Output only. The time diagnostic start.
   *
   * @param string $diagnosticStartTime
   */
  public function setDiagnosticStartTime($diagnosticStartTime)
  {
    $this->diagnosticStartTime = $diagnosticStartTime;
  }
  /**
   * @return string
   */
  public function getDiagnosticStartTime()
  {
    return $this->diagnosticStartTime;
  }
  /**
   * Output only. The diagnostic state.
   *
   * Accepted values: DIAGNOSTIC_STATE_UNSPECIFIED, IN_PROGRESS, SUCCEEDED,
   * FAILED
   *
   * @param self::DIAGNOSTIC_STATE_* $diagnosticState
   */
  public function setDiagnosticState($diagnosticState)
  {
    $this->diagnosticState = $diagnosticState;
  }
  /**
   * @return self::DIAGNOSTIC_STATE_*
   */
  public function getDiagnosticState()
  {
    return $this->diagnosticState;
  }
  /**
   * Output only. The namespace name.
   *
   * @param string $namespace
   */
  public function setNamespace($namespace)
  {
    $this->namespace = $namespace;
  }
  /**
   * @return string
   */
  public function getNamespace()
  {
    return $this->namespace;
  }
  /**
   * Output only. The object name.
   *
   * @param string $object
   */
  public function setObject($object)
  {
    $this->object = $object;
  }
  /**
   * @return string
   */
  public function getObject()
  {
    return $this->object;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(DeploymentDiagnosticData::class, 'Google_Service_OracleDatabase_DeploymentDiagnosticData');
