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

namespace Google\Service\Docs;

class ReplaceAllTextRequest extends \Google\Model
{
  protected $containsTextType = SubstringMatchCriteria::class;
  protected $containsTextDataType = '';
  /**
   * @var string
   */
  public $replaceText;
  protected $tabsCriteriaType = TabsCriteria::class;
  protected $tabsCriteriaDataType = '';

  /**
   * @param SubstringMatchCriteria
   */
  public function setContainsText(SubstringMatchCriteria $containsText)
  {
    $this->containsText = $containsText;
  }
  /**
   * @return SubstringMatchCriteria
   */
  public function getContainsText()
  {
    return $this->containsText;
  }
  /**
   * @param string
   */
  public function setReplaceText($replaceText)
  {
    $this->replaceText = $replaceText;
  }
  /**
   * @return string
   */
  public function getReplaceText()
  {
    return $this->replaceText;
  }
  /**
   * @param TabsCriteria
   */
  public function setTabsCriteria(TabsCriteria $tabsCriteria)
  {
    $this->tabsCriteria = $tabsCriteria;
  }
  /**
   * @return TabsCriteria
   */
  public function getTabsCriteria()
  {
    return $this->tabsCriteria;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ReplaceAllTextRequest::class, 'Google_Service_Docs_ReplaceAllTextRequest');
