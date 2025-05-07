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

namespace Google\Service\BigtableAdmin;

class Type extends \Google\Model
{
  protected $aggregateTypeType = GoogleBigtableAdminV2TypeAggregate::class;
  protected $aggregateTypeDataType = '';
  protected $arrayTypeType = GoogleBigtableAdminV2TypeArray::class;
  protected $arrayTypeDataType = '';
  protected $boolTypeType = GoogleBigtableAdminV2TypeBool::class;
  protected $boolTypeDataType = '';
  protected $bytesTypeType = GoogleBigtableAdminV2TypeBytes::class;
  protected $bytesTypeDataType = '';
  protected $dateTypeType = GoogleBigtableAdminV2TypeDate::class;
  protected $dateTypeDataType = '';
  protected $float32TypeType = GoogleBigtableAdminV2TypeFloat32::class;
  protected $float32TypeDataType = '';
  protected $float64TypeType = GoogleBigtableAdminV2TypeFloat64::class;
  protected $float64TypeDataType = '';
  protected $int64TypeType = GoogleBigtableAdminV2TypeInt64::class;
  protected $int64TypeDataType = '';
  protected $mapTypeType = GoogleBigtableAdminV2TypeMap::class;
  protected $mapTypeDataType = '';
  protected $stringTypeType = GoogleBigtableAdminV2TypeString::class;
  protected $stringTypeDataType = '';
  protected $structTypeType = GoogleBigtableAdminV2TypeStruct::class;
  protected $structTypeDataType = '';
  protected $timestampTypeType = GoogleBigtableAdminV2TypeTimestamp::class;
  protected $timestampTypeDataType = '';

  /**
   * @param GoogleBigtableAdminV2TypeAggregate
   */
  public function setAggregateType(GoogleBigtableAdminV2TypeAggregate $aggregateType)
  {
    $this->aggregateType = $aggregateType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeAggregate
   */
  public function getAggregateType()
  {
    return $this->aggregateType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeArray
   */
  public function setArrayType(GoogleBigtableAdminV2TypeArray $arrayType)
  {
    $this->arrayType = $arrayType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeArray
   */
  public function getArrayType()
  {
    return $this->arrayType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeBool
   */
  public function setBoolType(GoogleBigtableAdminV2TypeBool $boolType)
  {
    $this->boolType = $boolType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeBool
   */
  public function getBoolType()
  {
    return $this->boolType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeBytes
   */
  public function setBytesType(GoogleBigtableAdminV2TypeBytes $bytesType)
  {
    $this->bytesType = $bytesType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeBytes
   */
  public function getBytesType()
  {
    return $this->bytesType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeDate
   */
  public function setDateType(GoogleBigtableAdminV2TypeDate $dateType)
  {
    $this->dateType = $dateType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeDate
   */
  public function getDateType()
  {
    return $this->dateType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeFloat32
   */
  public function setFloat32Type(GoogleBigtableAdminV2TypeFloat32 $float32Type)
  {
    $this->float32Type = $float32Type;
  }
  /**
   * @return GoogleBigtableAdminV2TypeFloat32
   */
  public function getFloat32Type()
  {
    return $this->float32Type;
  }
  /**
   * @param GoogleBigtableAdminV2TypeFloat64
   */
  public function setFloat64Type(GoogleBigtableAdminV2TypeFloat64 $float64Type)
  {
    $this->float64Type = $float64Type;
  }
  /**
   * @return GoogleBigtableAdminV2TypeFloat64
   */
  public function getFloat64Type()
  {
    return $this->float64Type;
  }
  /**
   * @param GoogleBigtableAdminV2TypeInt64
   */
  public function setInt64Type(GoogleBigtableAdminV2TypeInt64 $int64Type)
  {
    $this->int64Type = $int64Type;
  }
  /**
   * @return GoogleBigtableAdminV2TypeInt64
   */
  public function getInt64Type()
  {
    return $this->int64Type;
  }
  /**
   * @param GoogleBigtableAdminV2TypeMap
   */
  public function setMapType(GoogleBigtableAdminV2TypeMap $mapType)
  {
    $this->mapType = $mapType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeMap
   */
  public function getMapType()
  {
    return $this->mapType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeString
   */
  public function setStringType(GoogleBigtableAdminV2TypeString $stringType)
  {
    $this->stringType = $stringType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeString
   */
  public function getStringType()
  {
    return $this->stringType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeStruct
   */
  public function setStructType(GoogleBigtableAdminV2TypeStruct $structType)
  {
    $this->structType = $structType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeStruct
   */
  public function getStructType()
  {
    return $this->structType;
  }
  /**
   * @param GoogleBigtableAdminV2TypeTimestamp
   */
  public function setTimestampType(GoogleBigtableAdminV2TypeTimestamp $timestampType)
  {
    $this->timestampType = $timestampType;
  }
  /**
   * @return GoogleBigtableAdminV2TypeTimestamp
   */
  public function getTimestampType()
  {
    return $this->timestampType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Type::class, 'Google_Service_BigtableAdmin_Type');
