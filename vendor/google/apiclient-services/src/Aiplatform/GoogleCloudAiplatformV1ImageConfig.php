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

class GoogleCloudAiplatformV1ImageConfig extends \Google\Model
{
  /**
   * The default behavior is unspecified. The model will decide whether to
   * generate images of people.
   */
  public const PERSON_GENERATION_PERSON_GENERATION_UNSPECIFIED = 'PERSON_GENERATION_UNSPECIFIED';
  /**
   * Allows the model to generate images of people, including adults and
   * children.
   */
  public const PERSON_GENERATION_ALLOW_ALL = 'ALLOW_ALL';
  /**
   * Allows the model to generate images of adults, but not children.
   */
  public const PERSON_GENERATION_ALLOW_ADULT = 'ALLOW_ADULT';
  /**
   * Prevents the model from generating images of people.
   */
  public const PERSON_GENERATION_ALLOW_NONE = 'ALLOW_NONE';
  /**
   * Unspecified value. The model will proceed with the default behavior, which
   * is to allow generation of prominent people.
   */
  public const PROMINENT_PEOPLE_PROMINENT_PEOPLE_UNSPECIFIED = 'PROMINENT_PEOPLE_UNSPECIFIED';
  /**
   * Allows the model to generate images of prominent people.
   */
  public const PROMINENT_PEOPLE_ALLOW_PROMINENT_PEOPLE = 'ALLOW_PROMINENT_PEOPLE';
  /**
   * Prevents the model from generating images of prominent people.
   */
  public const PROMINENT_PEOPLE_BLOCK_PROMINENT_PEOPLE = 'BLOCK_PROMINENT_PEOPLE';
  /**
   * Optional. The desired aspect ratio for the generated images. The following
   * aspect ratios are supported: "1:1" "2:3", "3:2" "3:4", "4:3" "4:5", "5:4"
   * "9:16", "16:9" "21:9"
   *
   * @var string
   */
  public $aspectRatio;
  protected $imageOutputOptionsType = GoogleCloudAiplatformV1ImageConfigImageOutputOptions::class;
  protected $imageOutputOptionsDataType = '';
  /**
   * Optional. Specifies the size of generated images. Supported values are
   * `1K`, `2K`, `4K`. If not specified, the model will use default value `1K`.
   *
   * @var string
   */
  public $imageSize;
  /**
   * Optional. Controls whether the model can generate people.
   *
   * @var string
   */
  public $personGeneration;
  /**
   * Optional. Controls whether prominent people (celebrities) generation is
   * allowed. If used with personGeneration, personGeneration enum would take
   * precedence. For instance, if ALLOW_NONE is set, all person generation would
   * be blocked. If this field is unspecified, the default behavior is to allow
   * prominent people.
   *
   * @var string
   */
  public $prominentPeople;

  /**
   * Optional. The desired aspect ratio for the generated images. The following
   * aspect ratios are supported: "1:1" "2:3", "3:2" "3:4", "4:3" "4:5", "5:4"
   * "9:16", "16:9" "21:9"
   *
   * @param string $aspectRatio
   */
  public function setAspectRatio($aspectRatio)
  {
    $this->aspectRatio = $aspectRatio;
  }
  /**
   * @return string
   */
  public function getAspectRatio()
  {
    return $this->aspectRatio;
  }
  /**
   * Optional. The image output format for generated images.
   *
   * @param GoogleCloudAiplatformV1ImageConfigImageOutputOptions $imageOutputOptions
   */
  public function setImageOutputOptions(GoogleCloudAiplatformV1ImageConfigImageOutputOptions $imageOutputOptions)
  {
    $this->imageOutputOptions = $imageOutputOptions;
  }
  /**
   * @return GoogleCloudAiplatformV1ImageConfigImageOutputOptions
   */
  public function getImageOutputOptions()
  {
    return $this->imageOutputOptions;
  }
  /**
   * Optional. Specifies the size of generated images. Supported values are
   * `1K`, `2K`, `4K`. If not specified, the model will use default value `1K`.
   *
   * @param string $imageSize
   */
  public function setImageSize($imageSize)
  {
    $this->imageSize = $imageSize;
  }
  /**
   * @return string
   */
  public function getImageSize()
  {
    return $this->imageSize;
  }
  /**
   * Optional. Controls whether the model can generate people.
   *
   * Accepted values: PERSON_GENERATION_UNSPECIFIED, ALLOW_ALL, ALLOW_ADULT,
   * ALLOW_NONE
   *
   * @param self::PERSON_GENERATION_* $personGeneration
   */
  public function setPersonGeneration($personGeneration)
  {
    $this->personGeneration = $personGeneration;
  }
  /**
   * @return self::PERSON_GENERATION_*
   */
  public function getPersonGeneration()
  {
    return $this->personGeneration;
  }
  /**
   * Optional. Controls whether prominent people (celebrities) generation is
   * allowed. If used with personGeneration, personGeneration enum would take
   * precedence. For instance, if ALLOW_NONE is set, all person generation would
   * be blocked. If this field is unspecified, the default behavior is to allow
   * prominent people.
   *
   * Accepted values: PROMINENT_PEOPLE_UNSPECIFIED, ALLOW_PROMINENT_PEOPLE,
   * BLOCK_PROMINENT_PEOPLE
   *
   * @param self::PROMINENT_PEOPLE_* $prominentPeople
   */
  public function setProminentPeople($prominentPeople)
  {
    $this->prominentPeople = $prominentPeople;
  }
  /**
   * @return self::PROMINENT_PEOPLE_*
   */
  public function getProminentPeople()
  {
    return $this->prominentPeople;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1ImageConfig::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1ImageConfig');
