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

namespace Google\Service\Docs;

class Link extends \Google\Model
{
  protected $bookmarkType = BookmarkLink::class;
  protected $bookmarkDataType = '';
  /**
   * @var string
   */
  public $bookmarkId;
  protected $headingType = HeadingLink::class;
  protected $headingDataType = '';
  /**
   * @var string
   */
  public $headingId;
  /**
   * @var string
   */
  public $tabId;
  /**
   * @var string
   */
  public $url;

  /**
   * @param BookmarkLink
   */
  public function setBookmark(BookmarkLink $bookmark)
  {
    $this->bookmark = $bookmark;
  }
  /**
   * @return BookmarkLink
   */
  public function getBookmark()
  {
    return $this->bookmark;
  }
  /**
   * @param string
   */
  public function setBookmarkId($bookmarkId)
  {
    $this->bookmarkId = $bookmarkId;
  }
  /**
   * @return string
   */
  public function getBookmarkId()
  {
    return $this->bookmarkId;
  }
  /**
   * @param HeadingLink
   */
  public function setHeading(HeadingLink $heading)
  {
    $this->heading = $heading;
  }
  /**
   * @return HeadingLink
   */
  public function getHeading()
  {
    return $this->heading;
  }
  /**
   * @param string
   */
  public function setHeadingId($headingId)
  {
    $this->headingId = $headingId;
  }
  /**
   * @return string
   */
  public function getHeadingId()
  {
    return $this->headingId;
  }
  /**
   * @param string
   */
  public function setTabId($tabId)
  {
    $this->tabId = $tabId;
  }
  /**
   * @return string
   */
  public function getTabId()
  {
    return $this->tabId;
  }
  /**
   * @param string
   */
  public function setUrl($url)
  {
    $this->url = $url;
  }
  /**
   * @return string
   */
  public function getUrl()
  {
    return $this->url;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Link::class, 'Google_Service_Docs_Link');
