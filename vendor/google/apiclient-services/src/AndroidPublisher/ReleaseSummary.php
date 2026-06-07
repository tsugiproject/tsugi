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

namespace Google\Service\AndroidPublisher;

class ReleaseSummary extends \Google\Collection
{
  /**
   * Not specified.
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_UNSPECIFIED = 'RELEASE_LIFECYCLE_STATE_UNSPECIFIED';
  /**
   * The release is not yet ready and can still be edited.
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_DRAFT = 'RELEASE_LIFECYCLE_STATE_DRAFT';
  /**
   * The release is ready to be sent for review and an action is required from
   * developer
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_NOT_SENT_FOR_REVIEW = 'RELEASE_LIFECYCLE_STATE_NOT_SENT_FOR_REVIEW';
  /**
   * Submitted and in review
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_IN_REVIEW = 'RELEASE_LIFECYCLE_STATE_IN_REVIEW';
  /**
   * Passed review and is ready to be published manually by developer
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_APPROVED_NOT_PUBLISHED = 'RELEASE_LIFECYCLE_STATE_APPROVED_NOT_PUBLISHED';
  /**
   * App was rejected in review
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_NOT_APPROVED = 'RELEASE_LIFECYCLE_STATE_NOT_APPROVED';
  /**
   * Available to users on the track. This includes fully- or partially-rolled
   * out releases and any halted release that can be resumed.
   */
  public const RELEASE_LIFECYCLE_STATE_RELEASE_LIFECYCLE_STATE_PUBLISHED = 'RELEASE_LIFECYCLE_STATE_PUBLISHED';
  protected $collection_key = 'activeArtifacts';
  protected $activeArtifactsType = ArtifactSummary::class;
  protected $activeArtifactsDataType = 'array';
  /**
   * The lifecycle state of a release.
   *
   * @var string
   */
  public $releaseLifecycleState;
  /**
   * Name of the release.
   *
   * @var string
   */
  public $releaseName;
  /**
   * Identifier for the track. [Learn more about track
   * names.](https://developers.google.com/android-publisher/tracks).
   *
   * @var string
   */
  public $track;

  /**
   * List of active artifacts on this release
   *
   * @param ArtifactSummary[] $activeArtifacts
   */
  public function setActiveArtifacts($activeArtifacts)
  {
    $this->activeArtifacts = $activeArtifacts;
  }
  /**
   * @return ArtifactSummary[]
   */
  public function getActiveArtifacts()
  {
    return $this->activeArtifacts;
  }
  /**
   * The lifecycle state of a release.
   *
   * Accepted values: RELEASE_LIFECYCLE_STATE_UNSPECIFIED,
   * RELEASE_LIFECYCLE_STATE_DRAFT, RELEASE_LIFECYCLE_STATE_NOT_SENT_FOR_REVIEW,
   * RELEASE_LIFECYCLE_STATE_IN_REVIEW,
   * RELEASE_LIFECYCLE_STATE_APPROVED_NOT_PUBLISHED,
   * RELEASE_LIFECYCLE_STATE_NOT_APPROVED, RELEASE_LIFECYCLE_STATE_PUBLISHED
   *
   * @param self::RELEASE_LIFECYCLE_STATE_* $releaseLifecycleState
   */
  public function setReleaseLifecycleState($releaseLifecycleState)
  {
    $this->releaseLifecycleState = $releaseLifecycleState;
  }
  /**
   * @return self::RELEASE_LIFECYCLE_STATE_*
   */
  public function getReleaseLifecycleState()
  {
    return $this->releaseLifecycleState;
  }
  /**
   * Name of the release.
   *
   * @param string $releaseName
   */
  public function setReleaseName($releaseName)
  {
    $this->releaseName = $releaseName;
  }
  /**
   * @return string
   */
  public function getReleaseName()
  {
    return $this->releaseName;
  }
  /**
   * Identifier for the track. [Learn more about track
   * names.](https://developers.google.com/android-publisher/tracks).
   *
   * @param string $track
   */
  public function setTrack($track)
  {
    $this->track = $track;
  }
  /**
   * @return string
   */
  public function getTrack()
  {
    return $this->track;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReleaseSummary::class, 'Google_Service_AndroidPublisher_ReleaseSummary');
