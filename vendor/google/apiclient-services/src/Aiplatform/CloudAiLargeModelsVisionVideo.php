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

class CloudAiLargeModelsVisionVideo extends \Google\Model
{
  /**
   * @var string
   */
  public $encodedVideo;
  /**
   * @var string
   */
  public $encoding;
  /**
   * @var string
   */
  public $uri;
  /**
   * @var string
   */
  public $video;

  /**
   * @param string
   */
  public function setEncodedVideo($encodedVideo)
  {
    $this->encodedVideo = $encodedVideo;
  }
  /**
   * @return string
   */
  public function getEncodedVideo()
  {
    return $this->encodedVideo;
  }
  /**
   * @param string
   */
  public function setEncoding($encoding)
  {
    $this->encoding = $encoding;
  }
  /**
   * @return string
   */
  public function getEncoding()
  {
    return $this->encoding;
  }
  /**
   * @param string
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
  /**
   * @return string
   */
  public function getUri()
  {
    return $this->uri;
  }
  /**
   * @param string
   */
  public function setVideo($video)
  {
    $this->video = $video;
  }
  /**
   * @return string
   */
  public function getVideo()
  {
    return $this->video;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CloudAiLargeModelsVisionVideo::class, 'Google_Service_Aiplatform_CloudAiLargeModelsVisionVideo');
