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

class NetworkProfilesListResponse extends \Google\Collection
{
  protected $collection_key = 'unreachables';
  /**
   * @var string
   */
  public $etag;
  /**
   * @var string
   */
  public $id;
  protected $itemsType = NetworkProfile::class;
  protected $itemsDataType = 'array';
  /**
   * @var string
   */
  public $kind;
  /**
   * @var string
   */
  public $nextPageToken;
  /**
   * @var string
   */
  public $selfLink;
  /**
   * @var string[]
   */
  public $unreachables;
  protected $warningType = NetworkProfilesListResponseWarning::class;
  protected $warningDataType = '';

  /**
   * @param string
   */
  public function setEtag($etag)
  {
    $this->etag = $etag;
  }
  /**
   * @return string
   */
  public function getEtag()
  {
    return $this->etag;
  }
  /**
   * @param string
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
   * @param NetworkProfile[]
   */
  public function setItems($items)
  {
    $this->items = $items;
  }
  /**
   * @return NetworkProfile[]
   */
  public function getItems()
  {
    return $this->items;
  }
  /**
   * @param string
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
   * @param string
   */
  public function setNextPageToken($nextPageToken)
  {
    $this->nextPageToken = $nextPageToken;
  }
  /**
   * @return string
   */
  public function getNextPageToken()
  {
    return $this->nextPageToken;
  }
  /**
   * @param string
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
   * @param string[]
   */
  public function setUnreachables($unreachables)
  {
    $this->unreachables = $unreachables;
  }
  /**
   * @return string[]
   */
  public function getUnreachables()
  {
    return $this->unreachables;
  }
  /**
   * @param NetworkProfilesListResponseWarning
   */
  public function setWarning(NetworkProfilesListResponseWarning $warning)
  {
    $this->warning = $warning;
  }
  /**
   * @return NetworkProfilesListResponseWarning
   */
  public function getWarning()
  {
    return $this->warning;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(NetworkProfilesListResponse::class, 'Google_Service_Compute_NetworkProfilesListResponse');
