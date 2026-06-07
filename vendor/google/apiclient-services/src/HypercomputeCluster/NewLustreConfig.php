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

namespace Google\Service\HypercomputeCluster;

class NewLustreConfig extends \Google\Model
{
  /**
   * Required. Immutable. Storage capacity of the instance in gibibytes (GiB).
   * Allowed values are between 18000 and 7632000.
   *
   * @var string
   */
  public $capacityGb;
  /**
   * Optional. Immutable. Description of the Managed Lustre instance. Maximum of
   * 2048 characters.
   *
   * @var string
   */
  public $description;
  /**
   * Required. Immutable. Filesystem name for this instance. This name is used
   * by client-side tools, including when mounting the instance. Must be 8
   * characters or less and can only contain letters and numbers.
   *
   * @var string
   */
  public $filesystem;
  /**
   * Required. Immutable. Name of the Managed Lustre instance to create, in the
   * format `projects/{project}/locations/{location}/instances/{instance}`
   *
   * @var string
   */
  public $lustre;
  /**
   * Optional. Immutable. Throughput of the instance in MB/s/TiB. Valid values
   * are 125, 250, 500, 1000. See [Performance tiers and maximum storage
   * capacities](https://cloud.google.com/managed-lustre/docs/create-
   * instance#performance-tiers) for more information.
   *
   * @var string
   */
  public $perUnitStorageThroughput;

  /**
   * Required. Immutable. Storage capacity of the instance in gibibytes (GiB).
   * Allowed values are between 18000 and 7632000.
   *
   * @param string $capacityGb
   */
  public function setCapacityGb($capacityGb)
  {
    $this->capacityGb = $capacityGb;
  }
  /**
   * @return string
   */
  public function getCapacityGb()
  {
    return $this->capacityGb;
  }
  /**
   * Optional. Immutable. Description of the Managed Lustre instance. Maximum of
   * 2048 characters.
   *
   * @param string $description
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
   * Required. Immutable. Filesystem name for this instance. This name is used
   * by client-side tools, including when mounting the instance. Must be 8
   * characters or less and can only contain letters and numbers.
   *
   * @param string $filesystem
   */
  public function setFilesystem($filesystem)
  {
    $this->filesystem = $filesystem;
  }
  /**
   * @return string
   */
  public function getFilesystem()
  {
    return $this->filesystem;
  }
  /**
   * Required. Immutable. Name of the Managed Lustre instance to create, in the
   * format `projects/{project}/locations/{location}/instances/{instance}`
   *
   * @param string $lustre
   */
  public function setLustre($lustre)
  {
    $this->lustre = $lustre;
  }
  /**
   * @return string
   */
  public function getLustre()
  {
    return $this->lustre;
  }
  /**
   * Optional. Immutable. Throughput of the instance in MB/s/TiB. Valid values
   * are 125, 250, 500, 1000. See [Performance tiers and maximum storage
   * capacities](https://cloud.google.com/managed-lustre/docs/create-
   * instance#performance-tiers) for more information.
   *
   * @param string $perUnitStorageThroughput
   */
  public function setPerUnitStorageThroughput($perUnitStorageThroughput)
  {
    $this->perUnitStorageThroughput = $perUnitStorageThroughput;
  }
  /**
   * @return string
   */
  public function getPerUnitStorageThroughput()
  {
    return $this->perUnitStorageThroughput;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NewLustreConfig::class, 'Google_Service_HypercomputeCluster_NewLustreConfig');
