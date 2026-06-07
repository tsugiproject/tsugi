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

namespace Google\Service\Bigquery;

class GeneratedColumn extends \Google\Model
{
  /**
   * Unspecified GeneratedMode will default to GENERATED_ALWAYS.
   */
  public const GENERATED_MODE_GENERATED_MODE_UNSPECIFIED = 'GENERATED_MODE_UNSPECIFIED';
  /**
   * Field can only have system generated values. Users cannot manually insert
   * values into the field.
   */
  public const GENERATED_MODE_GENERATED_ALWAYS = 'GENERATED_ALWAYS';
  /**
   * Use system generated values only if the user does not explicitly provide a
   * value.
   */
  public const GENERATED_MODE_GENERATED_BY_DEFAULT = 'GENERATED_BY_DEFAULT';
  protected $generatedExpressionInfoType = GeneratedExpressionInfo::class;
  protected $generatedExpressionInfoDataType = '';
  /**
   * Optional. Dictates when system generated values are used to populate the
   * field.
   *
   * @var string
   */
  public $generatedMode;

  /**
   * Definition of the expression used to generate the field.
   *
   * @param GeneratedExpressionInfo $generatedExpressionInfo
   */
  public function setGeneratedExpressionInfo(GeneratedExpressionInfo $generatedExpressionInfo)
  {
    $this->generatedExpressionInfo = $generatedExpressionInfo;
  }
  /**
   * @return GeneratedExpressionInfo
   */
  public function getGeneratedExpressionInfo()
  {
    return $this->generatedExpressionInfo;
  }
  /**
   * Optional. Dictates when system generated values are used to populate the
   * field.
   *
   * Accepted values: GENERATED_MODE_UNSPECIFIED, GENERATED_ALWAYS,
   * GENERATED_BY_DEFAULT
   *
   * @param self::GENERATED_MODE_* $generatedMode
   */
  public function setGeneratedMode($generatedMode)
  {
    $this->generatedMode = $generatedMode;
  }
  /**
   * @return self::GENERATED_MODE_*
   */
  public function getGeneratedMode()
  {
    return $this->generatedMode;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GeneratedColumn::class, 'Google_Service_Bigquery_GeneratedColumn');
