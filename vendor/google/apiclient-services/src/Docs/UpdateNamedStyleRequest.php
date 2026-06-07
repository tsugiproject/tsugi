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

class UpdateNamedStyleRequest extends \Google\Model
{
  /**
   * The NamedStyle fields that should be updated. At least `named_style_type`
   * must be specified. The root `named_style` is implied and should not be
   * specified. A single `"*"` can be used as short-hand for listing every
   * field. For example, to update the text style to bold, set `fields` to
   * include `"text_style"` and `"text_style.bold"`. To update the paragraph
   * style's alignment property, set `fields` to include `"paragraph_style"` and
   * `"paragraph_style.alignment"`. To reset a property to its default value,
   * include its field name in the field mask but leave the field itself unset.
   * Specifying `"text_style"` or `"paragraph_style"` with an empty TextStyle or
   * ParagraphStyle will reset all of its nested fields.
   *
   * @var string
   */
  public $fields;
  protected $namedStyleType = NamedStyle::class;
  protected $namedStyleDataType = '';
  /**
   * The document tab to update. By default, the update is applied to the first
   * tab.
   *
   * @var string
   */
  public $tabId;

  /**
   * The NamedStyle fields that should be updated. At least `named_style_type`
   * must be specified. The root `named_style` is implied and should not be
   * specified. A single `"*"` can be used as short-hand for listing every
   * field. For example, to update the text style to bold, set `fields` to
   * include `"text_style"` and `"text_style.bold"`. To update the paragraph
   * style's alignment property, set `fields` to include `"paragraph_style"` and
   * `"paragraph_style.alignment"`. To reset a property to its default value,
   * include its field name in the field mask but leave the field itself unset.
   * Specifying `"text_style"` or `"paragraph_style"` with an empty TextStyle or
   * ParagraphStyle will reset all of its nested fields.
   *
   * @param string $fields
   */
  public function setFields($fields)
  {
    $this->fields = $fields;
  }
  /**
   * @return string
   */
  public function getFields()
  {
    return $this->fields;
  }
  /**
   * The document style to update.
   *
   * @param NamedStyle $namedStyle
   */
  public function setNamedStyle(NamedStyle $namedStyle)
  {
    $this->namedStyle = $namedStyle;
  }
  /**
   * @return NamedStyle
   */
  public function getNamedStyle()
  {
    return $this->namedStyle;
  }
  /**
   * The document tab to update. By default, the update is applied to the first
   * tab.
   *
   * @param string $tabId
   */
  public function setTabId($tabId)
  {
    $this->tabId = $tabId;
  }
  /**
   * @return string
   */
  public function getTabId()
  {
    return $this->tabId;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(UpdateNamedStyleRequest::class, 'Google_Service_Docs_UpdateNamedStyleRequest');
