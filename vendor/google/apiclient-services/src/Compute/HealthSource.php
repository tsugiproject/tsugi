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

class HealthSource extends \Google\Collection
{
  public const SOURCE_TYPE_BACKEND_SERVICE = 'BACKEND_SERVICE';
  protected $collection_key = 'sources';
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
   * inserting a HealthSource. An up-to-date fingerprint must be provided in
   * order to patch the HealthSource; Otherwise, the request will fail with
   * error 412 conditionNotMet. To see the latest fingerprint, make a get()
   * request to retrieve the HealthSource.
   *
   * @var string
   */
  public $fingerprint;
  /**
   * URL to the HealthAggregationPolicy resource. Must be set. Must be regional
   * and in the same region as the HealthSource. Can be mutated.
   *
   * @var string
   */
  public $healthAggregationPolicy;
  /**
   * Output only. [Output Only] A unique identifier for this resource type. The
   * server generates this identifier.
   *
   * @var string
   */
  public $id;
  /**
   * Output only. [Output Only] Type of the resource. Alwayscompute#healthSource
   * for health sources.
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
   * Output only. [Output Only] URL of the region where the health source
   * resides. This field applies only to the regional resource. You must specify
   * this field as part of the HTTP request URL. It is not settable as a field
   * in the request body.
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
   * Specifies the type of the HealthSource. The only allowed value is
   * BACKEND_SERVICE. Must be specified when theHealthSource is created, and
   * cannot be mutated.
   *
   * @var string
   */
  public $sourceType;
  /**
   * URLs to the source resources. Must be size 1. Must be aBackendService if
   * the sourceType is BACKEND_SERVICE. TheBackendService must have load
   * balancing schemeINTERNAL or INTERNAL_MANAGED and must be regional and in
   * the same region as the HealthSource (cross-region deployment for
   * INTERNAL_MANAGED is not supported). TheBackendService may use only IGs,
   * MIGs, or NEGs of typeGCE_VM_IP or GCE_VM_IP_PORT. TheBackendService may not
   * use haPolicy. Can be mutated.
   *
   * @var string[]
   */
  public $sources;

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
   * inserting a HealthSource. An up-to-date fingerprint must be provided in
   * order to patch the HealthSource; Otherwise, the request will fail with
   * error 412 conditionNotMet. To see the latest fingerprint, make a get()
   * request to retrieve the HealthSource.
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
   * URL to the HealthAggregationPolicy resource. Must be set. Must be regional
   * and in the same region as the HealthSource. Can be mutated.
   *
   * @param string $healthAggregationPolicy
   */
  public function setHealthAggregationPolicy($healthAggregationPolicy)
  {
    $this->healthAggregationPolicy = $healthAggregationPolicy;
  }
  /**
   * @return string
   */
  public function getHealthAggregationPolicy()
  {
    return $this->healthAggregationPolicy;
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
   * Output only. [Output Only] Type of the resource. Alwayscompute#healthSource
   * for health sources.
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
   * Output only. [Output Only] URL of the region where the health source
   * resides. This field applies only to the regional resource. You must specify
   * this field as part of the HTTP request URL. It is not settable as a field
   * in the request body.
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
  /**
   * Specifies the type of the HealthSource. The only allowed value is
   * BACKEND_SERVICE. Must be specified when theHealthSource is created, and
   * cannot be mutated.
   *
   * Accepted values: BACKEND_SERVICE
   *
   * @param self::SOURCE_TYPE_* $sourceType
   */
  public function setSourceType($sourceType)
  {
    $this->sourceType = $sourceType;
  }
  /**
   * @return self::SOURCE_TYPE_*
   */
  public function getSourceType()
  {
    return $this->sourceType;
  }
  /**
   * URLs to the source resources. Must be size 1. Must be aBackendService if
   * the sourceType is BACKEND_SERVICE. TheBackendService must have load
   * balancing schemeINTERNAL or INTERNAL_MANAGED and must be regional and in
   * the same region as the HealthSource (cross-region deployment for
   * INTERNAL_MANAGED is not supported). TheBackendService may use only IGs,
   * MIGs, or NEGs of typeGCE_VM_IP or GCE_VM_IP_PORT. TheBackendService may not
   * use haPolicy. Can be mutated.
   *
   * @param string[] $sources
   */
  public function setSources($sources)
  {
    $this->sources = $sources;
  }
  /**
   * @return string[]
   */
  public function getSources()
  {
    return $this->sources;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(HealthSource::class, 'Google_Service_Compute_HealthSource');
