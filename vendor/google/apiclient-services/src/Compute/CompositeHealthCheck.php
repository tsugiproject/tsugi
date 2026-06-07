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

class CompositeHealthCheck extends \Google\Collection
{
  protected $collection_key = 'healthSources';
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
   * Fingerprint of this resource. A hash of the contents stored in this object.
   * This field is used in optimistic locking. This field will be ignored when
   * inserting a CompositeHealthCheck. An up-to-date fingerprint must be
   * provided in order to patch the CompositeHealthCheck; Otherwise, the request
   * will fail with error 412 conditionNotMet. To see the latest fingerprint,
   * make a get() request to retrieve the CompositeHealthCheck.
   *
   * @var string
   */
  public $fingerprint;
  /**
   * URL to the destination resource. Must be set. Must be aForwardingRule. The
   * ForwardingRule must have load balancing scheme INTERNAL orINTERNAL_MANAGED
   * and must be regional and in the same region as the CompositeHealthCheck
   * (cross-region deployment forINTERNAL_MANAGED is not supported). Can be
   * mutated.
   *
   * @var string
   */
  public $healthDestination;
  /**
   * URLs to the HealthSource resources whose results are AND'ed. I.e. he
   * aggregated result is is HEALTHY only if all sources are HEALTHY. Must have
   * at least 1. Must not have more than 10. Must be regional and in the same
   * region as theCompositeHealthCheck. Can be mutated.
   *
   * @var string[]
   */
  public $healthSources;
  /**
   * Output only. [Output Only] A unique identifier for this resource type. The
   * server generates this identifier.
   *
   * @var string
   */
  public $id;
  /**
   * Output only. [Output Only] Type of the resource.
   * Alwayscompute#compositeHealthCheck for composite health checks.
   *
   * @var string
   */
  public $kind;
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
   * Output only. [Output Only] URL of the region where the composite health
   * check resides. This field applies only to the regional resource. You must
   * specify this field as part of the HTTP request URL. It is not settable as a
   * field in the request body.
   *
   * @var string
   */
  public $region;
  /**
   * Output only. [Output Only] Server-defined URL for the resource.
   *
   * @var string
   */
  public $selfLink;
  /**
   * Output only. [Output Only] Server-defined URL with id for the resource.
   *
   * @var string
   */
  public $selfLinkWithId;

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
   * Fingerprint of this resource. A hash of the contents stored in this object.
   * This field is used in optimistic locking. This field will be ignored when
   * inserting a CompositeHealthCheck. An up-to-date fingerprint must be
   * provided in order to patch the CompositeHealthCheck; Otherwise, the request
   * will fail with error 412 conditionNotMet. To see the latest fingerprint,
   * make a get() request to retrieve the CompositeHealthCheck.
   *
   * @param string $fingerprint
   */
  public function setFingerprint($fingerprint)
  {
    $this->fingerprint = $fingerprint;
  }
  /**
   * @return string
   */
  public function getFingerprint()
  {
    return $this->fingerprint;
  }
  /**
   * URL to the destination resource. Must be set. Must be aForwardingRule. The
   * ForwardingRule must have load balancing scheme INTERNAL orINTERNAL_MANAGED
   * and must be regional and in the same region as the CompositeHealthCheck
   * (cross-region deployment forINTERNAL_MANAGED is not supported). Can be
   * mutated.
   *
   * @param string $healthDestination
   */
  public function setHealthDestination($healthDestination)
  {
    $this->healthDestination = $healthDestination;
  }
  /**
   * @return string
   */
  public function getHealthDestination()
  {
    return $this->healthDestination;
  }
  /**
   * URLs to the HealthSource resources whose results are AND'ed. I.e. he
   * aggregated result is is HEALTHY only if all sources are HEALTHY. Must have
   * at least 1. Must not have more than 10. Must be regional and in the same
   * region as theCompositeHealthCheck. Can be mutated.
   *
   * @param string[] $healthSources
   */
  public function setHealthSources($healthSources)
  {
    $this->healthSources = $healthSources;
  }
  /**
   * @return string[]
   */
  public function getHealthSources()
  {
    return $this->healthSources;
  }
  /**
   * Output only. [Output Only] A unique identifier for this resource type. The
   * server generates this identifier.
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
   * Output only. [Output Only] Type of the resource.
   * Alwayscompute#compositeHealthCheck for composite health checks.
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
   * Output only. [Output Only] URL of the region where the composite health
   * check resides. This field applies only to the regional resource. You must
   * specify this field as part of the HTTP request URL. It is not settable as a
   * field in the request body.
   *
   * @param string $region
   */
  public function setRegion($region)
  {
    $this->region = $region;
  }
  /**
   * @return string
   */
  public function getRegion()
  {
    return $this->region;
  }
  /**
   * Output only. [Output Only] Server-defined URL for the resource.
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
   * Output only. [Output Only] Server-defined URL with id for the resource.
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CompositeHealthCheck::class, 'Google_Service_Compute_CompositeHealthCheck');
