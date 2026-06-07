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

class RolloutPlan extends \Google\Collection
{
  /**
   * Unspecified value. Considered as ZONAL.
   */
  public const LOCATION_SCOPE_LOCATION_SCOPE_UNSPECIFIED = 'LOCATION_SCOPE_UNSPECIFIED';
  /**
   * Regional scope.
   */
  public const LOCATION_SCOPE_REGIONAL = 'REGIONAL';
  /**
   * Zonal scope.
   */
  public const LOCATION_SCOPE_ZONAL = 'ZONAL';
  protected $collection_key = 'waves';
  /**
   * Output only. [Output Only] Creation timestamp inRFC3339 text format.
   *
   * @var string
   */
  public $creationTimestamp;
  /**
   * An optional description of this resource. Provide this property when you
   * create the resource.
   *
   * @var string
   */
  public $description;
  /**
   * Output only. [Output Only] The unique identifier for the resource. This
   * identifier is defined by the server.
   *
   * @var string
   */
  public $id;
  /**
   * Output only. [Output Only] Type of the resource. Always compute#rolloutPlan
   * for rolloutPlans.
   *
   * @var string
   */
  public $kind;
  /**
   * The location scope of the rollout plan. If not specified, the location
   * scope is considered as ZONAL.
   *
   * @var string
   */
  public $locationScope;
  /**
   * Name of the resource. Provided by the client when the resource is created.
   * The name must be 1-63 characters long, and comply withRFC1035.
   * Specifically, the name must be 1-63 characters long and match the regular
   * expression `[a-z]([-a-z0-9]*[a-z0-9])?` which means the first character
   * must be a lowercase letter, and all following characters must be a dash,
   * lowercase letter, or digit, except the last character, which cannot be a
   * dash.
   *
   * @var string
   */
  public $name;
  /**
   * Output only. [Output Only] Server-defined fully-qualified URL for this
   * resource.
   *
   * @var string
   */
  public $selfLink;
  /**
   * Output only. [Output Only] Server-defined URL for this resource's resource
   * id.
   *
   * @var string
   */
  public $selfLinkWithId;
  protected $wavesType = RolloutPlanWave::class;
  protected $wavesDataType = 'array';

  /**
   * Output only. [Output Only] Creation timestamp inRFC3339 text format.
   *
   * @param string $creationTimestamp
   */
  public function setCreationTimestamp($creationTimestamp)
  {
    $this->creationTimestamp = $creationTimestamp;
  }
  /**
   * @return string
   */
  public function getCreationTimestamp()
  {
    return $this->creationTimestamp;
  }
  /**
   * An optional description of this resource. Provide this property when you
   * create the resource.
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
   * Output only. [Output Only] The unique identifier for the resource. This
   * identifier is defined by the server.
   *
   * @param string $id
   */
  public function setId($id)
  {
    $this->id = $id;
  }
  /**
   * @return string
   */
  public function getId()
  {
    return $this->id;
  }
  /**
   * Output only. [Output Only] Type of the resource. Always compute#rolloutPlan
   * for rolloutPlans.
   *
   * @param string $kind
   */
  public function setKind($kind)
  {
    $this->kind = $kind;
  }
  /**
   * @return string
   */
  public function getKind()
  {
    return $this->kind;
  }
  /**
   * The location scope of the rollout plan. If not specified, the location
   * scope is considered as ZONAL.
   *
   * Accepted values: LOCATION_SCOPE_UNSPECIFIED, REGIONAL, ZONAL
   *
   * @param self::LOCATION_SCOPE_* $locationScope
   */
  public function setLocationScope($locationScope)
  {
    $this->locationScope = $locationScope;
  }
  /**
   * @return self::LOCATION_SCOPE_*
   */
  public function getLocationScope()
  {
    return $this->locationScope;
  }
  /**
   * Name of the resource. Provided by the client when the resource is created.
   * The name must be 1-63 characters long, and comply withRFC1035.
   * Specifically, the name must be 1-63 characters long and match the regular
   * expression `[a-z]([-a-z0-9]*[a-z0-9])?` which means the first character
   * must be a lowercase letter, and all following characters must be a dash,
   * lowercase letter, or digit, except the last character, which cannot be a
   * dash.
   *
   * @param string $name
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
   * Output only. [Output Only] Server-defined fully-qualified URL for this
   * resource.
   *
   * @param string $selfLink
   */
  public function setSelfLink($selfLink)
  {
    $this->selfLink = $selfLink;
  }
  /**
   * @return string
   */
  public function getSelfLink()
  {
    return $this->selfLink;
  }
  /**
   * Output only. [Output Only] Server-defined URL for this resource's resource
   * id.
   *
   * @param string $selfLinkWithId
   */
  public function setSelfLinkWithId($selfLinkWithId)
  {
    $this->selfLinkWithId = $selfLinkWithId;
  }
  /**
   * @return string
   */
  public function getSelfLinkWithId()
  {
    return $this->selfLinkWithId;
  }
  /**
   * Required. The waves included in this rollout plan.
   *
   * @param RolloutPlanWave[] $waves
   */
  public function setWaves($waves)
  {
    $this->waves = $waves;
  }
  /**
   * @return RolloutPlanWave[]
   */
  public function getWaves()
  {
    return $this->waves;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(RolloutPlan::class, 'Google_Service_Compute_RolloutPlan');
