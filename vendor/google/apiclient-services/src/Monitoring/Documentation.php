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

namespace Google\Service\Monitoring;

class Documentation extends \Google\Collection
{
  protected $collection_key = 'links';
  /**
   * @var string
   */
  public $content;
  protected $linksType = Link::class;
  protected $linksDataType = 'array';
  /**
   * @var string
   */
  public $mimeType;
  /**
   * @var string
   */
  public $subject;

  /**
   * @param string
   */
  public function setContent($content)
  {
    $this->content = $content;
  }
  /**
   * @return string
   */
  public function getContent()
  {
    return $this->content;
  }
  /**
   * @param Link[]
   */
  public function setLinks($links)
  {
    $this->links = $links;
  }
  /**
   * @return Link[]
   */
  public function getLinks()
  {
    return $this->links;
  }
  /**
   * @param string
   */
  public function setMimeType($mimeType)
  {
    $this->mimeType = $mimeType;
  }
  /**
   * @return string
   */
  public function getMimeType()
  {
    return $this->mimeType;
  }
  /**
   * @param string
   */
  public function setSubject($subject)
  {
    $this->subject = $subject;
  }
  /**
   * @return string
   */
  public function getSubject()
  {
    return $this->subject;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Documentation::class, 'Google_Service_Monitoring_Documentation');
