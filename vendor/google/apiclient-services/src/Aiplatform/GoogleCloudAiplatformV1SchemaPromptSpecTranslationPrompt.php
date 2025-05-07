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

class GoogleCloudAiplatformV1SchemaPromptSpecTranslationPrompt extends \Google\Model
{
  protected $exampleType = GoogleCloudAiplatformV1SchemaPromptSpecTranslationExample::class;
  protected $exampleDataType = '';
  protected $optionType = GoogleCloudAiplatformV1SchemaPromptSpecTranslationOption::class;
  protected $optionDataType = '';
  protected $promptMessageType = GoogleCloudAiplatformV1SchemaPromptSpecPromptMessage::class;
  protected $promptMessageDataType = '';
  /**
   * @var string
   */
  public $sourceLanguageCode;
  /**
   * @var string
   */
  public $targetLanguageCode;

  /**
   * @param GoogleCloudAiplatformV1SchemaPromptSpecTranslationExample
   */
  public function setExample(GoogleCloudAiplatformV1SchemaPromptSpecTranslationExample $example)
  {
    $this->example = $example;
  }
  /**
   * @return GoogleCloudAiplatformV1SchemaPromptSpecTranslationExample
   */
  public function getExample()
  {
    return $this->example;
  }
  /**
   * @param GoogleCloudAiplatformV1SchemaPromptSpecTranslationOption
   */
  public function setOption(GoogleCloudAiplatformV1SchemaPromptSpecTranslationOption $option)
  {
    $this->option = $option;
  }
  /**
   * @return GoogleCloudAiplatformV1SchemaPromptSpecTranslationOption
   */
  public function getOption()
  {
    return $this->option;
  }
  /**
   * @param GoogleCloudAiplatformV1SchemaPromptSpecPromptMessage
   */
  public function setPromptMessage(GoogleCloudAiplatformV1SchemaPromptSpecPromptMessage $promptMessage)
  {
    $this->promptMessage = $promptMessage;
  }
  /**
   * @return GoogleCloudAiplatformV1SchemaPromptSpecPromptMessage
   */
  public function getPromptMessage()
  {
    return $this->promptMessage;
  }
  /**
   * @param string
   */
  public function setSourceLanguageCode($sourceLanguageCode)
  {
    $this->sourceLanguageCode = $sourceLanguageCode;
  }
  /**
   * @return string
   */
  public function getSourceLanguageCode()
  {
    return $this->sourceLanguageCode;
  }
  /**
   * @param string
   */
  public function setTargetLanguageCode($targetLanguageCode)
  {
    $this->targetLanguageCode = $targetLanguageCode;
  }
  /**
   * @return string
   */
  public function getTargetLanguageCode()
  {
    return $this->targetLanguageCode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1SchemaPromptSpecTranslationPrompt::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1SchemaPromptSpecTranslationPrompt');
