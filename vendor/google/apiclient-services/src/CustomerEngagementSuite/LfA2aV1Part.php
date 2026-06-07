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

namespace Google\Service\CustomerEngagementSuite;

class LfA2aV1Part extends \Google\Model
{
  /**
   * Arbitrary structured `data` as a JSON value (object, array, string, number,
   * boolean, or null).
   *
   * @var array
   */
  public $data;
  /**
   * An optional `filename` for the file (e.g., "document.pdf").
   *
   * @var string
   */
  public $filename;
  /**
   * The `media_type` (MIME type) of the part content (e.g., "text/plain",
   * "application/json", "image/png"). This field is available for all part
   * types.
   *
   * @var string
   */
  public $mediaType;
  /**
   * Optional. metadata associated with this part.
   *
   * @var array[]
   */
  public $metadata;
  /**
   * The `raw` byte content of a file. In JSON serialization, this is encoded as
   * a base64 string.
   *
   * @var string
   */
  public $raw;
  /**
   * The string content of the `text` part.
   *
   * @var string
   */
  public $text;
  /**
   * A `url` pointing to the file's content.
   *
   * @var string
   */
  public $url;

  /**
   * Arbitrary structured `data` as a JSON value (object, array, string, number,
   * boolean, or null).
   *
   * @param array $data
   */
  public function setData($data)
  {
    $this->data = $data;
  }
  /**
   * @return array
   */
  public function getData()
  {
    return $this->data;
  }
  /**
   * An optional `filename` for the file (e.g., "document.pdf").
   *
   * @param string $filename
   */
  public function setFilename($filename)
  {
    $this->filename = $filename;
  }
  /**
   * @return string
   */
  public function getFilename()
  {
    return $this->filename;
  }
  /**
   * The `media_type` (MIME type) of the part content (e.g., "text/plain",
   * "application/json", "image/png"). This field is available for all part
   * types.
   *
   * @param string $mediaType
   */
  public function setMediaType($mediaType)
  {
    $this->mediaType = $mediaType;
  }
  /**
   * @return string
   */
  public function getMediaType()
  {
    return $this->mediaType;
  }
  /**
   * Optional. metadata associated with this part.
   *
   * @param array[] $metadata
   */
  public function setMetadata($metadata)
  {
    $this->metadata = $metadata;
  }
  /**
   * @return array[]
   */
  public function getMetadata()
  {
    return $this->metadata;
  }
  /**
   * The `raw` byte content of a file. In JSON serialization, this is encoded as
   * a base64 string.
   *
   * @param string $raw
   */
  public function setRaw($raw)
  {
    $this->raw = $raw;
  }
  /**
   * @return string
   */
  public function getRaw()
  {
    return $this->raw;
  }
  /**
   * The string content of the `text` part.
   *
   * @param string $text
   */
  public function setText($text)
  {
    $this->text = $text;
  }
  /**
   * @return string
   */
  public function getText()
  {
    return $this->text;
  }
  /**
   * A `url` pointing to the file's content.
   *
   * @param string $url
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
class_alias(LfA2aV1Part::class, 'Google_Service_CustomerEngagementSuite_LfA2aV1Part');
