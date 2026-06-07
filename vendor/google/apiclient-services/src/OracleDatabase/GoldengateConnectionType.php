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

namespace Google\Service\OracleDatabase;

class GoldengateConnectionType extends \Google\Collection
{
  /**
   * Default unspecified value.
   */
  public const CONNECTION_TYPE_CONNECTION_TYPE_UNSPECIFIED = 'CONNECTION_TYPE_UNSPECIFIED';
  /**
   * Goldengate Connection Type category is GOLDENGATE.
   */
  public const CONNECTION_TYPE_GOLDENGATE = 'GOLDENGATE';
  /**
   * Goldengate Connection Type category is KAFKA.
   */
  public const CONNECTION_TYPE_KAFKA = 'KAFKA';
  /**
   * Goldengate Connection Type category is KAFKA_SCHEMA_REGISTRY.
   */
  public const CONNECTION_TYPE_KAFKA_SCHEMA_REGISTRY = 'KAFKA_SCHEMA_REGISTRY';
  /**
   * Goldengate Connection Type category is MYSQL.
   */
  public const CONNECTION_TYPE_MYSQL = 'MYSQL';
  /**
   * Goldengate Connection Type category is JAVA_MESSAGE_SERVICE.
   */
  public const CONNECTION_TYPE_JAVA_MESSAGE_SERVICE = 'JAVA_MESSAGE_SERVICE';
  /**
   * Goldengate Connection Type category is MICROSOFT_SQLSERVER.
   */
  public const CONNECTION_TYPE_MICROSOFT_SQLSERVER = 'MICROSOFT_SQLSERVER';
  /**
   * Goldengate Connection Type category is OCI_OBJECT_STORAGE.
   */
  public const CONNECTION_TYPE_OCI_OBJECT_STORAGE = 'OCI_OBJECT_STORAGE';
  /**
   * Goldengate Connection Type category is ORACLE.
   */
  public const CONNECTION_TYPE_ORACLE = 'ORACLE';
  /**
   * Goldengate Connection Type category is AZURE_DATA_LAKE_STORAGE.
   */
  public const CONNECTION_TYPE_AZURE_DATA_LAKE_STORAGE = 'AZURE_DATA_LAKE_STORAGE';
  /**
   * Goldengate Connection Type category is POSTGRESQL.
   */
  public const CONNECTION_TYPE_POSTGRESQL = 'POSTGRESQL';
  /**
   * Goldengate Connection Type category is AZURE_SYNAPSE_ANALYTICS.
   */
  public const CONNECTION_TYPE_AZURE_SYNAPSE_ANALYTICS = 'AZURE_SYNAPSE_ANALYTICS';
  /**
   * Goldengate Connection Type category is SNOWFLAKE.
   */
  public const CONNECTION_TYPE_SNOWFLAKE = 'SNOWFLAKE';
  /**
   * Goldengate Connection Type category is AMAZON_S3.
   */
  public const CONNECTION_TYPE_AMAZON_S3 = 'AMAZON_S3';
  /**
   * Goldengate Connection Type category is HDFS.
   */
  public const CONNECTION_TYPE_HDFS = 'HDFS';
  /**
   * Goldengate Connection Type category is ORACLE_AI_DATA_PLATFORM.
   */
  public const CONNECTION_TYPE_ORACLE_AI_DATA_PLATFORM = 'ORACLE_AI_DATA_PLATFORM';
  /**
   * Goldengate Connection Type category is ORACLE_NOSQL.
   */
  public const CONNECTION_TYPE_ORACLE_NOSQL = 'ORACLE_NOSQL';
  /**
   * Goldengate Connection Type category is MONGODB.
   */
  public const CONNECTION_TYPE_MONGODB = 'MONGODB';
  /**
   * Goldengate Connection Type category is AMAZON_KINESIS.
   */
  public const CONNECTION_TYPE_AMAZON_KINESIS = 'AMAZON_KINESIS';
  /**
   * Goldengate Connection Type category is AMAZON_REDSHIFT.
   */
  public const CONNECTION_TYPE_AMAZON_REDSHIFT = 'AMAZON_REDSHIFT';
  /**
   * Goldengate Connection Type category is DB2.
   */
  public const CONNECTION_TYPE_DB2 = 'DB2';
  /**
   * Goldengate Connection Type category is REDIS.
   */
  public const CONNECTION_TYPE_REDIS = 'REDIS';
  /**
   * Goldengate Connection Type category is ELASTICSEARCH.
   */
  public const CONNECTION_TYPE_ELASTICSEARCH = 'ELASTICSEARCH';
  /**
   * Goldengate Connection Type category is GENERIC.
   */
  public const CONNECTION_TYPE_GENERIC = 'GENERIC';
  /**
   * Goldengate Connection Type category is GOOGLE_CLOUD_STORAGE.
   */
  public const CONNECTION_TYPE_GOOGLE_CLOUD_STORAGE = 'GOOGLE_CLOUD_STORAGE';
  /**
   * Goldengate Connection Type category is GOOGLE_BIGQUERY.
   */
  public const CONNECTION_TYPE_GOOGLE_BIGQUERY = 'GOOGLE_BIGQUERY';
  /**
   * Goldengate Connection Type category is DATABRICKS.
   */
  public const CONNECTION_TYPE_DATABRICKS = 'DATABRICKS';
  /**
   * Goldengate Connection Type category is GOOGLE_PUBSUB.
   */
  public const CONNECTION_TYPE_GOOGLE_PUBSUB = 'GOOGLE_PUBSUB';
  /**
   * Goldengate Connection Type category is MICROSOFT_FABRIC.
   */
  public const CONNECTION_TYPE_MICROSOFT_FABRIC = 'MICROSOFT_FABRIC';
  /**
   * Goldengate Connection Type category is ICEBERG.
   */
  public const CONNECTION_TYPE_ICEBERG = 'ICEBERG';
  protected $collection_key = 'technologyTypes';
  /**
   * Output only. The connection type of the Goldengate Connection Type
   * resource.
   *
   * @var string
   */
  public $connectionType;
  /**
   * Identifier. The name of the Goldengate Connection Type resource with the
   * format: projects/{project}/locations/{region}/goldengateConnectionTypes/{go
   * ldengate_connection_type}
   *
   * @var string
   */
  public $name;
  /**
   * Output only. The technology type of the Goldengate Connection Type
   * resource.
   *
   * @var string[]
   */
  public $technologyTypes;

  /**
   * Output only. The connection type of the Goldengate Connection Type
   * resource.
   *
   * Accepted values: CONNECTION_TYPE_UNSPECIFIED, GOLDENGATE, KAFKA,
   * KAFKA_SCHEMA_REGISTRY, MYSQL, JAVA_MESSAGE_SERVICE, MICROSOFT_SQLSERVER,
   * OCI_OBJECT_STORAGE, ORACLE, AZURE_DATA_LAKE_STORAGE, POSTGRESQL,
   * AZURE_SYNAPSE_ANALYTICS, SNOWFLAKE, AMAZON_S3, HDFS,
   * ORACLE_AI_DATA_PLATFORM, ORACLE_NOSQL, MONGODB, AMAZON_KINESIS,
   * AMAZON_REDSHIFT, DB2, REDIS, ELASTICSEARCH, GENERIC, GOOGLE_CLOUD_STORAGE,
   * GOOGLE_BIGQUERY, DATABRICKS, GOOGLE_PUBSUB, MICROSOFT_FABRIC, ICEBERG
   *
   * @param self::CONNECTION_TYPE_* $connectionType
   */
  public function setConnectionType($connectionType)
  {
    $this->connectionType = $connectionType;
  }
  /**
   * @return self::CONNECTION_TYPE_*
   */
  public function getConnectionType()
  {
    return $this->connectionType;
  }
  /**
   * Identifier. The name of the Goldengate Connection Type resource with the
   * format: projects/{project}/locations/{region}/goldengateConnectionTypes/{go
   * ldengate_connection_type}
   *
   * @param string $name
   */
  public function setName($name)
  {
    $this->name = $name;
  }
  /**
   * @return string
   */
  public function getName()
  {
    return $this->name;
  }
  /**
   * Output only. The technology type of the Goldengate Connection Type
   * resource.
   *
   * @param string[] $technologyTypes
   */
  public function setTechnologyTypes($technologyTypes)
  {
    $this->technologyTypes = $technologyTypes;
  }
  /**
   * @return string[]
   */
  public function getTechnologyTypes()
  {
    return $this->technologyTypes;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateConnectionType::class, 'Google_Service_OracleDatabase_GoldengateConnectionType');
