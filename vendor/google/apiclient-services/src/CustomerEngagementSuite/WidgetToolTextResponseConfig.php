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

class WidgetToolTextResponseConfig extends \Google\Model
{
  /**
   * Unspecified type.
   */
  public const TYPE_TYPE_UNSPECIFIED = 'TYPE_UNSPECIFIED';
  /**
   * The LLM dynamically decides whether to generate a text response alongside
   * the widget based on the conversation context.
   */
  public const TYPE_NONE = 'NONE';
  /**
   * The LLM is explicitly required to generate a text response.
   */
  public const TYPE_LLM_GENERATED = 'LLM_GENERATED';
  /**
   * A pre-defined static text response is always used.
   */
  public const TYPE_STATIC = 'STATIC';
  /**
   * Optional. The static text response to return when type is STATIC.
   *
   * @var string
   */
  public $staticText;
  /**
   * Optional. Instruction for the LLM on how to generate the text response.
   * Used as the description for the text response parameter if type is
   * LLM_GENERATED.
   *
   * @var string
   */
  public $textResponseInstruction;
  /**
   * Optional. The strategy for providing the text response.
   *
   * @var string
   */
  public $type;

  /**
   * Optional. The static text response to return when type is STATIC.
   *
   * @param string $staticText
   */
  public function setStaticText($staticText)
  {
    $this->staticText = $staticText;
  }
  /**
   * @return string
   */
  public function getStaticText()
  {
    return $this->staticText;
  }
  /**
   * Optional. Instruction for the LLM on how to generate the text response.
   * Used as the description for the text response parameter if type is
   * LLM_GENERATED.
   *
   * @param string $textResponseInstruction
   */
  public function setTextResponseInstruction($textResponseInstruction)
  {
    $this->textResponseInstruction = $textResponseInstruction;
  }
  /**
   * @return string
   */
  public function getTextResponseInstruction()
  {
    return $this->textResponseInstruction;
  }
  /**
   * Optional. The strategy for providing the text response.
   *
   * Accepted values: TYPE_UNSPECIFIED, NONE, LLM_GENERATED, STATIC
   *
   * @param self::TYPE_* $type
   */
  public function setType($type)
  {
    $this->type = $type;
  }
  /**
   * @return self::TYPE_*
   */
  public function getType()
  {
    return $this->type;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(WidgetToolTextResponseConfig::class, 'Google_Service_CustomerEngagementSuite_WidgetToolTextResponseConfig');
