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

class FieldSource extends \Google\Model
{
  /**
   * The alias name for a field that has already been aliased within a different
   * ProjectedField type elsewhere in the query model. The alias must be defined
   * in the QueryBuilderConfig's field_sources list, otherwise the model is
   * invalid.
   *
   * @var string
   */
  public $aliasRef;
  /**
   * The type of the selected field. This comes from the schema. Can be one of
   * the BigQuery data types: - STRING - INT64 - FLOAT64 - BOOL - TIMESTAMP -
   * DATE - RECORD - JSON
   *
   * @var string
   */
  public $columnType;
  /**
   * The fully qualified, dot-delimited path to the selected atomic field (the
   * leaf value). This path is used for primary selection and actions like
   * drill-down or projection.The path components should match the exact field
   * names or keys as they appear in the underlying data schema. For JSON
   * fields, this means respecting the original casing (e.g., camelCase or
   * snake_case as present in the JSON).To reference field names containing
   * special characters (e.g., hyphens, spaces), enclose the individual path
   * segment in backticks (`).Examples: * json_payload.labels.message *
   * json_payload.request_id * httpRequest.status * json_payload.\my-custom-
   * field`.value *jsonPayload.`my key with spaces`.data`
   *
   * @var string
   */
  public $field;
  /**
   * Whether the field is a JSON field, or has a parent that is a JSON field.
   * This value is used to determine JSON extractions in generated SQL queries.
   * Note that this is_json flag may be true when the column_type is not JSON if
   * the parent is a JSON field. Ex: - A json_payload.message field might have
   * is_json=true, since the 'json_payload' parent is of type JSON, and
   * columnType='STRING' if the 'message' field is of type STRING.
   *
   * @var bool
   */
  public $isJson;
  /**
   * The dot-delimited path of the parent container that holds the target
   * field.This path defines the structural hierarchy and is essential for
   * correctly generating SQL when field keys contain special characters (e.g.,
   * dots or brackets).Example: json_payload.labels (This points to the 'labels'
   * object). This is an empty string if the target field is at the root level.
   *
   * @var string
   */
  public $parentPath;
  protected $projectedFieldType = ProjectedField::class;
  protected $projectedFieldDataType = '';

  /**
   * The alias name for a field that has already been aliased within a different
   * ProjectedField type elsewhere in the query model. The alias must be defined
   * in the QueryBuilderConfig's field_sources list, otherwise the model is
   * invalid.
   *
   * @param string $aliasRef
   */
  public function setAliasRef($aliasRef)
  {
    $this->aliasRef = $aliasRef;
  }
  /**
   * @return string
   */
  public function getAliasRef()
  {
    return $this->aliasRef;
  }
  /**
   * The type of the selected field. This comes from the schema. Can be one of
   * the BigQuery data types: - STRING - INT64 - FLOAT64 - BOOL - TIMESTAMP -
   * DATE - RECORD - JSON
   *
   * @param string $columnType
   */
  public function setColumnType($columnType)
  {
    $this->columnType = $columnType;
  }
  /**
   * @return string
   */
  public function getColumnType()
  {
    return $this->columnType;
  }
  /**
   * The fully qualified, dot-delimited path to the selected atomic field (the
   * leaf value). This path is used for primary selection and actions like
   * drill-down or projection.The path components should match the exact field
   * names or keys as they appear in the underlying data schema. For JSON
   * fields, this means respecting the original casing (e.g., camelCase or
   * snake_case as present in the JSON).To reference field names containing
   * special characters (e.g., hyphens, spaces), enclose the individual path
   * segment in backticks (`).Examples: * json_payload.labels.message *
   * json_payload.request_id * httpRequest.status * json_payload.\my-custom-
   * field`.value *jsonPayload.`my key with spaces`.data`
   *
   * @param string $field
   */
  public function setField($field)
  {
    $this->field = $field;
  }
  /**
   * @return string
   */
  public function getField()
  {
    return $this->field;
  }
  /**
   * Whether the field is a JSON field, or has a parent that is a JSON field.
   * This value is used to determine JSON extractions in generated SQL queries.
   * Note that this is_json flag may be true when the column_type is not JSON if
   * the parent is a JSON field. Ex: - A json_payload.message field might have
   * is_json=true, since the 'json_payload' parent is of type JSON, and
   * columnType='STRING' if the 'message' field is of type STRING.
   *
   * @param bool $isJson
   */
  public function setIsJson($isJson)
  {
    $this->isJson = $isJson;
  }
  /**
   * @return bool
   */
  public function getIsJson()
  {
    return $this->isJson;
  }
  /**
   * The dot-delimited path of the parent container that holds the target
   * field.This path defines the structural hierarchy and is essential for
   * correctly generating SQL when field keys contain special characters (e.g.,
   * dots or brackets).Example: json_payload.labels (This points to the 'labels'
   * object). This is an empty string if the target field is at the root level.
   *
   * @param string $parentPath
   */
  public function setParentPath($parentPath)
  {
    $this->parentPath = $parentPath;
  }
  /**
   * @return string
   */
  public function getParentPath()
  {
    return $this->parentPath;
  }
  /**
   * A projected field option for when a user wants to use a field with some
   * additional transformations such as casting or extractions.
   *
   * @param ProjectedField $projectedField
   */
  public function setProjectedField(ProjectedField $projectedField)
  {
    $this->projectedField = $projectedField;
  }
  /**
   * @return ProjectedField
   */
  public function getProjectedField()
  {
    return $this->projectedField;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(FieldSource::class, 'Google_Service_Logging_FieldSource');
