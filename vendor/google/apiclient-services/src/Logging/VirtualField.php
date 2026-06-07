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

namespace Google\Service\Logging;

class VirtualField extends \Google\Collection
{
  /**
   * Invalid value, do not use.
   */
  public const VIRTUAL_FIELD_TYPE_VIRTUAL_FIELD_TYPE_UNSPECIFIED = 'VIRTUAL_FIELD_TYPE_UNSPECIFIED';
  /**
   * Creates a virtual field by selecting the first non-null value from the list
   * of fields specified in underlying_field_sources, similar to a COALESCE
   * function in SQL.
   */
  public const VIRTUAL_FIELD_TYPE_COALESCE = 'COALESCE';
  protected $collection_key = 'underlyingFieldSources';
  protected $underlyingFieldSourcesType = FieldSource::class;
  protected $underlyingFieldSourcesDataType = 'array';
  /**
   * Required. The type of the virtual field.
   *
   * @var string
   */
  public $virtualFieldType;

  /**
   * The field sources that will be used to create the virtual field, based on
   * the semantics of the virtual field type.The field sources must follow these
   * rules, based on the virtual field type: - For
   * VIRTUAL_FIELD_TYPE_UNSPECIFIED, this field must be empty. - For COALESCE,
   * this field must be non-empty and include a minimum of two field sources.
   * The underlying field sources must be actual projected fields that represent
   * actual schema fields and that must not be transformed and aggregated in any
   * way, except for casting. The type of all the underlying field sources must
   * be equivalent so that picking one of them would result in the same value
   * type.
   *
   * @param FieldSource[] $underlyingFieldSources
   */
  public function setUnderlyingFieldSources($underlyingFieldSources)
  {
    $this->underlyingFieldSources = $underlyingFieldSources;
  }
  /**
   * @return FieldSource[]
   */
  public function getUnderlyingFieldSources()
  {
    return $this->underlyingFieldSources;
  }
  /**
   * Required. The type of the virtual field.
   *
   * Accepted values: VIRTUAL_FIELD_TYPE_UNSPECIFIED, COALESCE
   *
   * @param self::VIRTUAL_FIELD_TYPE_* $virtualFieldType
   */
  public function setVirtualFieldType($virtualFieldType)
  {
    $this->virtualFieldType = $virtualFieldType;
  }
  /**
   * @return self::VIRTUAL_FIELD_TYPE_*
   */
  public function getVirtualFieldType()
  {
    return $this->virtualFieldType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(VirtualField::class, 'Google_Service_Logging_VirtualField');
