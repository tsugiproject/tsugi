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

class GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlockLayoutImageBlock extends \Google\Model
{
  protected $annotationsType = GoogleCloudDocumentaiV1DocumentAnnotations::class;
  protected $annotationsDataType = '';
  /**
   * Optional. Asset id of the inline image. If set, find the image content in
   * the blob_assets field.
   *
   * @var string
   */
  public $blobAssetId;
  /**
   * Optional. Data uri of the image. It is composed of four parts: a prefix
   * (data:), a MIME type indicating the type of data, an optional base64 token
   * if non-textual, and the data itself: data:,
   *
   * @var string
   */
  public $dataUri;
  /**
   * Optional. Google Cloud Storage uri of the image.
   *
   * @var string
   */
  public $gcsUri;
  /**
   * Text extracted from the image using OCR or alt text describing the image.
   *
   * @var string
   */
  public $imageText;
  /**
   * Mime type of the image. An IANA published [media type (MIME type)]
   * (https://www.iana.org/assignments/media-types/media-types.xhtml).
   *
   * @var string
   */
  public $mimeType;

  /**
   * Annotation of the image block.
   *
   * @param GoogleCloudDocumentaiV1DocumentAnnotations $annotations
   */
  public function setAnnotations(GoogleCloudDocumentaiV1DocumentAnnotations $annotations)
  {
    $this->annotations = $annotations;
  }
  /**
   * @return GoogleCloudDocumentaiV1DocumentAnnotations
   */
  public function getAnnotations()
  {
    return $this->annotations;
  }
  /**
   * Optional. Asset id of the inline image. If set, find the image content in
   * the blob_assets field.
   *
   * @param string $blobAssetId
   */
  public function setBlobAssetId($blobAssetId)
  {
    $this->blobAssetId = $blobAssetId;
  }
  /**
   * @return string
   */
  public function getBlobAssetId()
  {
    return $this->blobAssetId;
  }
  /**
   * Optional. Data uri of the image. It is composed of four parts: a prefix
   * (data:), a MIME type indicating the type of data, an optional base64 token
   * if non-textual, and the data itself: data:,
   *
   * @param string $dataUri
   */
  public function setDataUri($dataUri)
  {
    $this->dataUri = $dataUri;
  }
  /**
   * @return string
   */
  public function getDataUri()
  {
    return $this->dataUri;
  }
  /**
   * Optional. Google Cloud Storage uri of the image.
   *
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
   * Text extracted from the image using OCR or alt text describing the image.
   *
   * @param string $imageText
   */
  public function setImageText($imageText)
  {
    $this->imageText = $imageText;
  }
  /**
   * @return string
   */
  public function getImageText()
  {
    return $this->imageText;
  }
  /**
   * Mime type of the image. An IANA published [media type (MIME type)]
   * (https://www.iana.org/assignments/media-types/media-types.xhtml).
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
class_alias(GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlockLayoutImageBlock::class, 'Google_Service_Document_GoogleCloudDocumentaiV1DocumentDocumentLayoutDocumentLayoutBlockLayoutImageBlock');
