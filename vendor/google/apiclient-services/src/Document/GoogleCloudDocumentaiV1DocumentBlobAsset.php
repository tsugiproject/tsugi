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

namespace Google\Service\Document;

class GoogleCloudDocumentaiV1DocumentBlobAsset extends \Google\Model
{
  /**
   * Optional. The id of the blob asset.
   *
   * @var string
   */
  public $assetId;
  /**
   * Optional. The content of the blob asset, for example, image bytes.
   *
   * @var string
   */
  public $content;
  /**
   * The mime type of the blob asset. An IANA published [media type (MIME
   * type)](https://www.iana.org/assignments/media-types/media-types.xhtml).
   *
   * @var string
   */
  public $mimeType;

  /**
   * Optional. The id of the blob asset.
   *
   * @param string $assetId
   */
  public function setAssetId($assetId)
  {
    $this->assetId = $assetId;
  }
  /**
   * @return string
   */
  public function getAssetId()
  {
    return $this->assetId;
  }
  /**
   * Optional. The content of the blob asset, for example, image bytes.
   *
   * @param string $content
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
   * The mime type of the blob asset. An IANA published [media type (MIME
   * type)](https://www.iana.org/assignments/media-types/media-types.xhtml).
   *
   * @param string $mimeType
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
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudDocumentaiV1DocumentBlobAsset::class, 'Google_Service_Document_GoogleCloudDocumentaiV1DocumentBlobAsset');
