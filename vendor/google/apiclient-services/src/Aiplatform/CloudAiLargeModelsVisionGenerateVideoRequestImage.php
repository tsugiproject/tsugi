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

namespace Google\Service\Aiplatform;

class CloudAiLargeModelsVisionGenerateVideoRequestImage extends \Google\Model
{
  /**
   * Blob ID of the image. This is used for storing the large images in the
   * request.
   *
   * @var string
   */
  public $blobId;
  /**
   * Base64 encoded bytes string representing the image.
   *
   * @var string
   */
  public $bytesBase64Encoded;
  /**
   * @var string
   */
  public $gcsUri;
  /**
   * The MIME type of the content of the image. Only the images in below listed
   * MIME types are supported. - image/jpeg - image/png
   *
   * @var string
   */
  public $mimeType;

  /**
   * Blob ID of the image. This is used for storing the large images in the
   * request.
   *
   * @param string $blobId
   */
  public function setBlobId($blobId)
  {
    $this->blobId = $blobId;
  }
  /**
   * @return string
   */
  public function getBlobId()
  {
    return $this->blobId;
  }
  /**
   * Base64 encoded bytes string representing the image.
   *
   * @param string $bytesBase64Encoded
   */
  public function setBytesBase64Encoded($bytesBase64Encoded)
  {
    $this->bytesBase64Encoded = $bytesBase64Encoded;
  }
  /**
   * @return string
   */
  public function getBytesBase64Encoded()
  {
    return $this->bytesBase64Encoded;
  }
  /**
   * @param string $gcsUri
   */
  public function setGcsUri($gcsUri)
  {
    $this->gcsUri = $gcsUri;
  }
  /**
   * @return string
   */
  public function getGcsUri()
  {
    return $this->gcsUri;
  }
  /**
   * The MIME type of the content of the image. Only the images in below listed
   * MIME types are supported. - image/jpeg - image/png
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
class_alias(CloudAiLargeModelsVisionGenerateVideoRequestImage::class, 'Google_Service_Aiplatform_CloudAiLargeModelsVisionGenerateVideoRequestImage');
