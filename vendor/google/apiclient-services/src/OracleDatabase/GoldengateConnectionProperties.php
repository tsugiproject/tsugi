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

class GoldengateConnectionProperties extends \Google\Collection
{
  /**
   * Connection type unspecified.
   */
  public const CONNECTION_TYPE_GOLDENGATE_CONNECTION_TYPE_UNSPECIFIED = 'GOLDENGATE_CONNECTION_TYPE_UNSPECIFIED';
  /**
   * Goldengate connection type.
   */
  public const CONNECTION_TYPE_GOLDENGATE = 'GOLDENGATE';
  /**
   * Kafka connection type.
   */
  public const CONNECTION_TYPE_KAFKA = 'KAFKA';
  /**
   * Kafka schema registry connection type.
   */
  public const CONNECTION_TYPE_KAFKA_SCHEMA_REGISTRY = 'KAFKA_SCHEMA_REGISTRY';
  /**
   * MySQL connection type.
   */
  public const CONNECTION_TYPE_MYSQL = 'MYSQL';
  /**
   * Java message service connection type.
   */
  public const CONNECTION_TYPE_JAVA_MESSAGE_SERVICE = 'JAVA_MESSAGE_SERVICE';
  /**
   * Microsoft SQL Server connection type.
   */
  public const CONNECTION_TYPE_MICROSOFT_SQLSERVER = 'MICROSOFT_SQLSERVER';
  /**
   * OCI object storage connection type.
   */
  public const CONNECTION_TYPE_OCI_OBJECT_STORAGE = 'OCI_OBJECT_STORAGE';
  /**
   * Oracle connection type.
   */
  public const CONNECTION_TYPE_ORACLE = 'ORACLE';
  /**
   * Azure data lake storage connection type.
   */
  public const CONNECTION_TYPE_AZURE_DATA_LAKE_STORAGE = 'AZURE_DATA_LAKE_STORAGE';
  /**
   * PostgreSQL connection type.
   */
  public const CONNECTION_TYPE_POSTGRESQL = 'POSTGRESQL';
  /**
   * Azure synapse analytics connection type.
   */
  public const CONNECTION_TYPE_AZURE_SYNAPSE_ANALYTICS = 'AZURE_SYNAPSE_ANALYTICS';
  /**
   * Snowflake connection type.
   */
  public const CONNECTION_TYPE_SNOWFLAKE = 'SNOWFLAKE';
  /**
   * Amazon S3 connection type.
   */
  public const CONNECTION_TYPE_AMAZON_S3 = 'AMAZON_S3';
  /**
   * HDFS connection type.
   */
  public const CONNECTION_TYPE_HDFS = 'HDFS';
  /**
   * Oracle AI data platform connection type.
   */
  public const CONNECTION_TYPE_ORACLE_AI_DATA_PLATFORM = 'ORACLE_AI_DATA_PLATFORM';
  /**
   * Oracle NoSQL connection type.
   */
  public const CONNECTION_TYPE_ORACLE_NOSQL = 'ORACLE_NOSQL';
  /**
   * MongoDB connection type.
   */
  public const CONNECTION_TYPE_MONGODB = 'MONGODB';
  /**
   * Amazon Kinesis connection type.
   */
  public const CONNECTION_TYPE_AMAZON_KINESIS = 'AMAZON_KINESIS';
  /**
   * Amazon Redshift connection type.
   */
  public const CONNECTION_TYPE_AMAZON_REDSHIFT = 'AMAZON_REDSHIFT';
  /**
   * DB2 connection type.
   */
  public const CONNECTION_TYPE_DB2 = 'DB2';
  /**
   * Redis connection type.
   */
  public const CONNECTION_TYPE_REDIS = 'REDIS';
  /**
   * Elasticsearch connection type.
   */
  public const CONNECTION_TYPE_ELASTICSEARCH = 'ELASTICSEARCH';
  /**
   * Generic connection type.
   */
  public const CONNECTION_TYPE_GENERIC = 'GENERIC';
  /**
   * Google Cloud Storage connection type.
   */
  public const CONNECTION_TYPE_GOOGLE_CLOUD_STORAGE = 'GOOGLE_CLOUD_STORAGE';
  /**
   * Google BigQuery connection type.
   */
  public const CONNECTION_TYPE_GOOGLE_BIGQUERY = 'GOOGLE_BIGQUERY';
  /**
   * Databricks connection type.
   */
  public const CONNECTION_TYPE_DATABRICKS = 'DATABRICKS';
  /**
   * Google Pub/Sub connection type.
   */
  public const CONNECTION_TYPE_GOOGLE_PUBSUB = 'GOOGLE_PUBSUB';
  /**
   * Microsoft Fabric connection type.
   */
  public const CONNECTION_TYPE_MICROSOFT_FABRIC = 'MICROSOFT_FABRIC';
  /**
   * Iceberg connection type.
   */
  public const CONNECTION_TYPE_ICEBERG = 'ICEBERG';
  /**
   * Default unspecified value.
   */
  public const LIFECYCLE_STATE_GOLDENGATE_CONNECTION_LIFECYCLE_STATE_UNSPECIFIED = 'GOLDENGATE_CONNECTION_LIFECYCLE_STATE_UNSPECIFIED';
  /**
   * Indicates that the resource is in provisioning state.
   */
  public const LIFECYCLE_STATE_CREATING = 'CREATING';
  /**
   * Indicates that the resource is in active state.
   */
  public const LIFECYCLE_STATE_ACTIVE = 'ACTIVE';
  /**
   * Indicates that the resource is in updating state.
   */
  public const LIFECYCLE_STATE_UPDATING = 'UPDATING';
  /**
   * Indicates that the resource is in deleting state.
   */
  public const LIFECYCLE_STATE_DELETING = 'DELETING';
  /**
   * Indicates that the resource is in deleted state.
   */
  public const LIFECYCLE_STATE_DELETED = 'DELETED';
  /**
   * Indicates that the resource is in failed state.
   */
  public const LIFECYCLE_STATE_FAILED = 'FAILED';
  /**
   * Default unspecified value.
   */
  public const ROUTING_METHOD_GOLDENGATE_CONNECTION_ROUTING_METHOD_UNSPECIFIED = 'GOLDENGATE_CONNECTION_ROUTING_METHOD_UNSPECIFIED';
  /**
   * Network traffic flows from the assigned deployment's private endpoint
   * through the deployment's subnet.
   */
  public const ROUTING_METHOD_SHARED_DEPLOYMENT_ENDPOINT = 'SHARED_DEPLOYMENT_ENDPOINT';
  /**
   * A dedicated private endpoint is created in the target VCN subnet for the
   * connection.
   */
  public const ROUTING_METHOD_DEDICATED_ENDPOINT = 'DEDICATED_ENDPOINT';
  protected $collection_key = 'ingressIpAddresses';
  protected $amazonKinesisConnectionPropertiesType = GoldengateAmazonKinesisConnectionProperties::class;
  protected $amazonKinesisConnectionPropertiesDataType = '';
  protected $amazonRedshiftConnectionPropertiesType = GoldengateAmazonRedshiftConnectionProperties::class;
  protected $amazonRedshiftConnectionPropertiesDataType = '';
  protected $amazonS3ConnectionPropertiesType = GoldengateAmazonS3ConnectionProperties::class;
  protected $amazonS3ConnectionPropertiesDataType = '';
  protected $azureDataLakeStorageConnectionPropertiesType = GoldengateAzureDataLakeStorageConnectionProperties::class;
  protected $azureDataLakeStorageConnectionPropertiesDataType = '';
  protected $azureSynapseAnalyticsConnectionPropertiesType = GoldengateAzureSynapseAnalyticsConnectionProperties::class;
  protected $azureSynapseAnalyticsConnectionPropertiesDataType = '';
  /**
   * Required. The connection type.
   *
   * @var string
   */
  public $connectionType;
  protected $databricksConnectionPropertiesType = GoldengateDatabricksConnectionProperties::class;
  protected $databricksConnectionPropertiesDataType = '';
  protected $db2ConnectionPropertiesType = GoldengateDb2ConnectionProperties::class;
  protected $db2ConnectionPropertiesDataType = '';
  /**
   * Optional. Metadata about this specific object.
   *
   * @var string
   */
  public $description;
  /**
   * Required. An object's Display Name.
   *
   * @var string
   */
  public $displayName;
  protected $elasticsearchConnectionPropertiesType = GoldengateElasticsearchConnectionProperties::class;
  protected $elasticsearchConnectionPropertiesDataType = '';
  protected $genericConnectionPropertiesType = GoldengateGenericConnectionProperties::class;
  protected $genericConnectionPropertiesDataType = '';
  protected $goldengateConnectionPropertiesType = GoldengateGoldengateConnectionProperties::class;
  protected $goldengateConnectionPropertiesDataType = '';
  protected $googleBigQueryConnectionPropertiesType = GoldengateGoogleBigQueryConnectionProperties::class;
  protected $googleBigQueryConnectionPropertiesDataType = '';
  protected $googleCloudStorageConnectionPropertiesType = GoldengateGoogleCloudStorageConnectionProperties::class;
  protected $googleCloudStorageConnectionPropertiesDataType = '';
  protected $googlePubsubConnectionPropertiesType = GoldengateGooglePubsubConnectionProperties::class;
  protected $googlePubsubConnectionPropertiesDataType = '';
  protected $hdfsConnectionPropertiesType = GoldengateHdfsConnectionProperties::class;
  protected $hdfsConnectionPropertiesDataType = '';
  protected $icebergConnectionPropertiesType = GoldengateIcebergConnectionProperties::class;
  protected $icebergConnectionPropertiesDataType = '';
  /**
   * Output only. The Ingress IPs of the GoldengateConnection.
   *
   * @var string[]
   */
  public $ingressIpAddresses;
  protected $javaMessageServiceConnectionPropertiesType = GoldengateJavaMessageServiceConnectionProperties::class;
  protected $javaMessageServiceConnectionPropertiesDataType = '';
  protected $kafkaConnectionPropertiesType = GoldengateKafkaConnectionProperties::class;
  protected $kafkaConnectionPropertiesDataType = '';
  protected $kafkaSchemaRegistryConnectionPropertiesType = GoldengateKafkaSchemaRegistryConnectionProperties::class;
  protected $kafkaSchemaRegistryConnectionPropertiesDataType = '';
  /**
   * Output only. Describes the object's current state in detail. For example,
   * it can be used to provide actionable information for a resource in a Failed
   * state.
   *
   * @var string
   */
  public $lifecycleDetails;
  /**
   * Output only. The lifecycle state of the connection.
   *
   * @var string
   */
  public $lifecycleState;
  protected $microsoftFabricConnectionPropertiesType = GoldengateMicrosoftFabricConnectionProperties::class;
  protected $microsoftFabricConnectionPropertiesDataType = '';
  protected $microsoftSqlserverConnectionPropertiesType = GoldengateMicrosoftSqlserverConnectionProperties::class;
  protected $microsoftSqlserverConnectionPropertiesDataType = '';
  protected $mongodbConnectionPropertiesType = GoldengateMongodbConnectionProperties::class;
  protected $mongodbConnectionPropertiesDataType = '';
  protected $mysqlConnectionPropertiesType = GoldengateMysqlConnectionProperties::class;
  protected $mysqlConnectionPropertiesDataType = '';
  protected $ociObjectStorageConnectionPropertiesType = GoldengateOciObjectStorageConnectionProperties::class;
  protected $ociObjectStorageConnectionPropertiesDataType = '';
  /**
   * Output only. The [OCID] of the connection being referenced.
   *
   * @var string
   */
  public $ocid;
  protected $oracleAiDataPlatformConnectionPropertiesType = GoldengateOracleAIDataPlatformConnectionProperties::class;
  protected $oracleAiDataPlatformConnectionPropertiesDataType = '';
  protected $oracleConnectionPropertiesType = GoldengateOracleConnectionProperties::class;
  protected $oracleConnectionPropertiesDataType = '';
  protected $oracleNosqlConnectionPropertiesType = GoldengateOracleNosqlConnectionProperties::class;
  protected $oracleNosqlConnectionPropertiesDataType = '';
  protected $postgresqlConnectionPropertiesType = GoldengatePostgresqlConnectionProperties::class;
  protected $postgresqlConnectionPropertiesDataType = '';
  protected $redisConnectionPropertiesType = GoldengateRedisConnectionProperties::class;
  protected $redisConnectionPropertiesDataType = '';
  /**
   * Optional. The routing method for the GoldengateConnection.
   *
   * @var string
   */
  public $routingMethod;
  protected $snowflakeConnectionPropertiesType = GoldengateSnowflakeConnectionProperties::class;
  protected $snowflakeConnectionPropertiesDataType = '';
  /**
   * Output only. The time the resource was last updated.
   *
   * @var string
   */
  public $updateTime;

  /**
   * Properties for an Amazon Kinesis connection.
   *
   * @param GoldengateAmazonKinesisConnectionProperties $amazonKinesisConnectionProperties
   */
  public function setAmazonKinesisConnectionProperties(GoldengateAmazonKinesisConnectionProperties $amazonKinesisConnectionProperties)
  {
    $this->amazonKinesisConnectionProperties = $amazonKinesisConnectionProperties;
  }
  /**
   * @return GoldengateAmazonKinesisConnectionProperties
   */
  public function getAmazonKinesisConnectionProperties()
  {
    return $this->amazonKinesisConnectionProperties;
  }
  /**
   * Properties for an Amazon Redshift connection.
   *
   * @param GoldengateAmazonRedshiftConnectionProperties $amazonRedshiftConnectionProperties
   */
  public function setAmazonRedshiftConnectionProperties(GoldengateAmazonRedshiftConnectionProperties $amazonRedshiftConnectionProperties)
  {
    $this->amazonRedshiftConnectionProperties = $amazonRedshiftConnectionProperties;
  }
  /**
   * @return GoldengateAmazonRedshiftConnectionProperties
   */
  public function getAmazonRedshiftConnectionProperties()
  {
    return $this->amazonRedshiftConnectionProperties;
  }
  /**
   * Properties for an Amazon S3 connection.
   *
   * @param GoldengateAmazonS3ConnectionProperties $amazonS3ConnectionProperties
   */
  public function setAmazonS3ConnectionProperties(GoldengateAmazonS3ConnectionProperties $amazonS3ConnectionProperties)
  {
    $this->amazonS3ConnectionProperties = $amazonS3ConnectionProperties;
  }
  /**
   * @return GoldengateAmazonS3ConnectionProperties
   */
  public function getAmazonS3ConnectionProperties()
  {
    return $this->amazonS3ConnectionProperties;
  }
  /**
   * Properties for an Azure Data Lake Storage Connection.
   *
   * @param GoldengateAzureDataLakeStorageConnectionProperties $azureDataLakeStorageConnectionProperties
   */
  public function setAzureDataLakeStorageConnectionProperties(GoldengateAzureDataLakeStorageConnectionProperties $azureDataLakeStorageConnectionProperties)
  {
    $this->azureDataLakeStorageConnectionProperties = $azureDataLakeStorageConnectionProperties;
  }
  /**
   * @return GoldengateAzureDataLakeStorageConnectionProperties
   */
  public function getAzureDataLakeStorageConnectionProperties()
  {
    return $this->azureDataLakeStorageConnectionProperties;
  }
  /**
   * Properties for an Azure Synapse Analytics connection.
   *
   * @param GoldengateAzureSynapseAnalyticsConnectionProperties $azureSynapseAnalyticsConnectionProperties
   */
  public function setAzureSynapseAnalyticsConnectionProperties(GoldengateAzureSynapseAnalyticsConnectionProperties $azureSynapseAnalyticsConnectionProperties)
  {
    $this->azureSynapseAnalyticsConnectionProperties = $azureSynapseAnalyticsConnectionProperties;
  }
  /**
   * @return GoldengateAzureSynapseAnalyticsConnectionProperties
   */
  public function getAzureSynapseAnalyticsConnectionProperties()
  {
    return $this->azureSynapseAnalyticsConnectionProperties;
  }
  /**
   * Required. The connection type.
   *
   * Accepted values: GOLDENGATE_CONNECTION_TYPE_UNSPECIFIED, GOLDENGATE, KAFKA,
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
   * Properties for a Databricks connection.
   *
   * @param GoldengateDatabricksConnectionProperties $databricksConnectionProperties
   */
  public function setDatabricksConnectionProperties(GoldengateDatabricksConnectionProperties $databricksConnectionProperties)
  {
    $this->databricksConnectionProperties = $databricksConnectionProperties;
  }
  /**
   * @return GoldengateDatabricksConnectionProperties
   */
  public function getDatabricksConnectionProperties()
  {
    return $this->databricksConnectionProperties;
  }
  /**
   * Properties for a DB2 connection.
   *
   * @param GoldengateDb2ConnectionProperties $db2ConnectionProperties
   */
  public function setDb2ConnectionProperties(GoldengateDb2ConnectionProperties $db2ConnectionProperties)
  {
    $this->db2ConnectionProperties = $db2ConnectionProperties;
  }
  /**
   * @return GoldengateDb2ConnectionProperties
   */
  public function getDb2ConnectionProperties()
  {
    return $this->db2ConnectionProperties;
  }
  /**
   * Optional. Metadata about this specific object.
   *
   * @param string $description
   */
  public function setDescription($description)
  {
    $this->description = $description;
  }
  /**
   * @return string
   */
  public function getDescription()
  {
    return $this->description;
  }
  /**
   * Required. An object's Display Name.
   *
   * @param string $displayName
   */
  public function setDisplayName($displayName)
  {
    $this->displayName = $displayName;
  }
  /**
   * @return string
   */
  public function getDisplayName()
  {
    return $this->displayName;
  }
  /**
   * Properties for an Elasticsearch connection.
   *
   * @param GoldengateElasticsearchConnectionProperties $elasticsearchConnectionProperties
   */
  public function setElasticsearchConnectionProperties(GoldengateElasticsearchConnectionProperties $elasticsearchConnectionProperties)
  {
    $this->elasticsearchConnectionProperties = $elasticsearchConnectionProperties;
  }
  /**
   * @return GoldengateElasticsearchConnectionProperties
   */
  public function getElasticsearchConnectionProperties()
  {
    return $this->elasticsearchConnectionProperties;
  }
  /**
   * Properties for a Generic Connection.
   *
   * @param GoldengateGenericConnectionProperties $genericConnectionProperties
   */
  public function setGenericConnectionProperties(GoldengateGenericConnectionProperties $genericConnectionProperties)
  {
    $this->genericConnectionProperties = $genericConnectionProperties;
  }
  /**
   * @return GoldengateGenericConnectionProperties
   */
  public function getGenericConnectionProperties()
  {
    return $this->genericConnectionProperties;
  }
  /**
   * Properties for a Goldengate Connection.
   *
   * @param GoldengateGoldengateConnectionProperties $goldengateConnectionProperties
   */
  public function setGoldengateConnectionProperties(GoldengateGoldengateConnectionProperties $goldengateConnectionProperties)
  {
    $this->goldengateConnectionProperties = $goldengateConnectionProperties;
  }
  /**
   * @return GoldengateGoldengateConnectionProperties
   */
  public function getGoldengateConnectionProperties()
  {
    return $this->goldengateConnectionProperties;
  }
  /**
   * Properties for a Google BigQuery Connection.
   *
   * @param GoldengateGoogleBigQueryConnectionProperties $googleBigQueryConnectionProperties
   */
  public function setGoogleBigQueryConnectionProperties(GoldengateGoogleBigQueryConnectionProperties $googleBigQueryConnectionProperties)
  {
    $this->googleBigQueryConnectionProperties = $googleBigQueryConnectionProperties;
  }
  /**
   * @return GoldengateGoogleBigQueryConnectionProperties
   */
  public function getGoogleBigQueryConnectionProperties()
  {
    return $this->googleBigQueryConnectionProperties;
  }
  /**
   * Properties for a Google Cloud Storage Connection.
   *
   * @param GoldengateGoogleCloudStorageConnectionProperties $googleCloudStorageConnectionProperties
   */
  public function setGoogleCloudStorageConnectionProperties(GoldengateGoogleCloudStorageConnectionProperties $googleCloudStorageConnectionProperties)
  {
    $this->googleCloudStorageConnectionProperties = $googleCloudStorageConnectionProperties;
  }
  /**
   * @return GoldengateGoogleCloudStorageConnectionProperties
   */
  public function getGoogleCloudStorageConnectionProperties()
  {
    return $this->googleCloudStorageConnectionProperties;
  }
  /**
   * Properties for a Google Pub/Sub connection.
   *
   * @param GoldengateGooglePubsubConnectionProperties $googlePubsubConnectionProperties
   */
  public function setGooglePubsubConnectionProperties(GoldengateGooglePubsubConnectionProperties $googlePubsubConnectionProperties)
  {
    $this->googlePubsubConnectionProperties = $googlePubsubConnectionProperties;
  }
  /**
   * @return GoldengateGooglePubsubConnectionProperties
   */
  public function getGooglePubsubConnectionProperties()
  {
    return $this->googlePubsubConnectionProperties;
  }
  /**
   * Properties for an HDFS connection.
   *
   * @param GoldengateHdfsConnectionProperties $hdfsConnectionProperties
   */
  public function setHdfsConnectionProperties(GoldengateHdfsConnectionProperties $hdfsConnectionProperties)
  {
    $this->hdfsConnectionProperties = $hdfsConnectionProperties;
  }
  /**
   * @return GoldengateHdfsConnectionProperties
   */
  public function getHdfsConnectionProperties()
  {
    return $this->hdfsConnectionProperties;
  }
  /**
   * Properties for an Iceberg connection.
   *
   * @param GoldengateIcebergConnectionProperties $icebergConnectionProperties
   */
  public function setIcebergConnectionProperties(GoldengateIcebergConnectionProperties $icebergConnectionProperties)
  {
    $this->icebergConnectionProperties = $icebergConnectionProperties;
  }
  /**
   * @return GoldengateIcebergConnectionProperties
   */
  public function getIcebergConnectionProperties()
  {
    return $this->icebergConnectionProperties;
  }
  /**
   * Output only. The Ingress IPs of the GoldengateConnection.
   *
   * @param string[] $ingressIpAddresses
   */
  public function setIngressIpAddresses($ingressIpAddresses)
  {
    $this->ingressIpAddresses = $ingressIpAddresses;
  }
  /**
   * @return string[]
   */
  public function getIngressIpAddresses()
  {
    return $this->ingressIpAddresses;
  }
  /**
   * Properties for a Java Message Service connection.
   *
   * @param GoldengateJavaMessageServiceConnectionProperties $javaMessageServiceConnectionProperties
   */
  public function setJavaMessageServiceConnectionProperties(GoldengateJavaMessageServiceConnectionProperties $javaMessageServiceConnectionProperties)
  {
    $this->javaMessageServiceConnectionProperties = $javaMessageServiceConnectionProperties;
  }
  /**
   * @return GoldengateJavaMessageServiceConnectionProperties
   */
  public function getJavaMessageServiceConnectionProperties()
  {
    return $this->javaMessageServiceConnectionProperties;
  }
  /**
   * Properties for a Kafka Connection.
   *
   * @param GoldengateKafkaConnectionProperties $kafkaConnectionProperties
   */
  public function setKafkaConnectionProperties(GoldengateKafkaConnectionProperties $kafkaConnectionProperties)
  {
    $this->kafkaConnectionProperties = $kafkaConnectionProperties;
  }
  /**
   * @return GoldengateKafkaConnectionProperties
   */
  public function getKafkaConnectionProperties()
  {
    return $this->kafkaConnectionProperties;
  }
  /**
   * Properties for a Kafka Schema Registry Connection.
   *
   * @param GoldengateKafkaSchemaRegistryConnectionProperties $kafkaSchemaRegistryConnectionProperties
   */
  public function setKafkaSchemaRegistryConnectionProperties(GoldengateKafkaSchemaRegistryConnectionProperties $kafkaSchemaRegistryConnectionProperties)
  {
    $this->kafkaSchemaRegistryConnectionProperties = $kafkaSchemaRegistryConnectionProperties;
  }
  /**
   * @return GoldengateKafkaSchemaRegistryConnectionProperties
   */
  public function getKafkaSchemaRegistryConnectionProperties()
  {
    return $this->kafkaSchemaRegistryConnectionProperties;
  }
  /**
   * Output only. Describes the object's current state in detail. For example,
   * it can be used to provide actionable information for a resource in a Failed
   * state.
   *
   * @param string $lifecycleDetails
   */
  public function setLifecycleDetails($lifecycleDetails)
  {
    $this->lifecycleDetails = $lifecycleDetails;
  }
  /**
   * @return string
   */
  public function getLifecycleDetails()
  {
    return $this->lifecycleDetails;
  }
  /**
   * Output only. The lifecycle state of the connection.
   *
   * Accepted values: GOLDENGATE_CONNECTION_LIFECYCLE_STATE_UNSPECIFIED,
   * CREATING, ACTIVE, UPDATING, DELETING, DELETED, FAILED
   *
   * @param self::LIFECYCLE_STATE_* $lifecycleState
   */
  public function setLifecycleState($lifecycleState)
  {
    $this->lifecycleState = $lifecycleState;
  }
  /**
   * @return self::LIFECYCLE_STATE_*
   */
  public function getLifecycleState()
  {
    return $this->lifecycleState;
  }
  /**
   * Properties for a Microsoft Fabric connection.
   *
   * @param GoldengateMicrosoftFabricConnectionProperties $microsoftFabricConnectionProperties
   */
  public function setMicrosoftFabricConnectionProperties(GoldengateMicrosoftFabricConnectionProperties $microsoftFabricConnectionProperties)
  {
    $this->microsoftFabricConnectionProperties = $microsoftFabricConnectionProperties;
  }
  /**
   * @return GoldengateMicrosoftFabricConnectionProperties
   */
  public function getMicrosoftFabricConnectionProperties()
  {
    return $this->microsoftFabricConnectionProperties;
  }
  /**
   * Properties for a Microsoft SQL Server connection.
   *
   * @param GoldengateMicrosoftSqlserverConnectionProperties $microsoftSqlserverConnectionProperties
   */
  public function setMicrosoftSqlserverConnectionProperties(GoldengateMicrosoftSqlserverConnectionProperties $microsoftSqlserverConnectionProperties)
  {
    $this->microsoftSqlserverConnectionProperties = $microsoftSqlserverConnectionProperties;
  }
  /**
   * @return GoldengateMicrosoftSqlserverConnectionProperties
   */
  public function getMicrosoftSqlserverConnectionProperties()
  {
    return $this->microsoftSqlserverConnectionProperties;
  }
  /**
   * Properties for a MongoDB connection.
   *
   * @param GoldengateMongodbConnectionProperties $mongodbConnectionProperties
   */
  public function setMongodbConnectionProperties(GoldengateMongodbConnectionProperties $mongodbConnectionProperties)
  {
    $this->mongodbConnectionProperties = $mongodbConnectionProperties;
  }
  /**
   * @return GoldengateMongodbConnectionProperties
   */
  public function getMongodbConnectionProperties()
  {
    return $this->mongodbConnectionProperties;
  }
  /**
   * Properties for a Mysql Connection.
   *
   * @param GoldengateMysqlConnectionProperties $mysqlConnectionProperties
   */
  public function setMysqlConnectionProperties(GoldengateMysqlConnectionProperties $mysqlConnectionProperties)
  {
    $this->mysqlConnectionProperties = $mysqlConnectionProperties;
  }
  /**
   * @return GoldengateMysqlConnectionProperties
   */
  public function getMysqlConnectionProperties()
  {
    return $this->mysqlConnectionProperties;
  }
  /**
   * Properties for an OCI Object Storage Connection.
   *
   * @param GoldengateOciObjectStorageConnectionProperties $ociObjectStorageConnectionProperties
   */
  public function setOciObjectStorageConnectionProperties(GoldengateOciObjectStorageConnectionProperties $ociObjectStorageConnectionProperties)
  {
    $this->ociObjectStorageConnectionProperties = $ociObjectStorageConnectionProperties;
  }
  /**
   * @return GoldengateOciObjectStorageConnectionProperties
   */
  public function getOciObjectStorageConnectionProperties()
  {
    return $this->ociObjectStorageConnectionProperties;
  }
  /**
   * Output only. The [OCID] of the connection being referenced.
   *
   * @param string $ocid
   */
  public function setOcid($ocid)
  {
    $this->ocid = $ocid;
  }
  /**
   * @return string
   */
  public function getOcid()
  {
    return $this->ocid;
  }
  /**
   * Properties for an Oracle AI Data Platform connection.
   *
   * @param GoldengateOracleAIDataPlatformConnectionProperties $oracleAiDataPlatformConnectionProperties
   */
  public function setOracleAiDataPlatformConnectionProperties(GoldengateOracleAIDataPlatformConnectionProperties $oracleAiDataPlatformConnectionProperties)
  {
    $this->oracleAiDataPlatformConnectionProperties = $oracleAiDataPlatformConnectionProperties;
  }
  /**
   * @return GoldengateOracleAIDataPlatformConnectionProperties
   */
  public function getOracleAiDataPlatformConnectionProperties()
  {
    return $this->oracleAiDataPlatformConnectionProperties;
  }
  /**
   * Properties for an Oracle Database Connection.
   *
   * @param GoldengateOracleConnectionProperties $oracleConnectionProperties
   */
  public function setOracleConnectionProperties(GoldengateOracleConnectionProperties $oracleConnectionProperties)
  {
    $this->oracleConnectionProperties = $oracleConnectionProperties;
  }
  /**
   * @return GoldengateOracleConnectionProperties
   */
  public function getOracleConnectionProperties()
  {
    return $this->oracleConnectionProperties;
  }
  /**
   * Properties for an Oracle NoSQL connection.
   *
   * @param GoldengateOracleNosqlConnectionProperties $oracleNosqlConnectionProperties
   */
  public function setOracleNosqlConnectionProperties(GoldengateOracleNosqlConnectionProperties $oracleNosqlConnectionProperties)
  {
    $this->oracleNosqlConnectionProperties = $oracleNosqlConnectionProperties;
  }
  /**
   * @return GoldengateOracleNosqlConnectionProperties
   */
  public function getOracleNosqlConnectionProperties()
  {
    return $this->oracleNosqlConnectionProperties;
  }
  /**
   * Properties for a PostgreSQL connection.
   *
   * @param GoldengatePostgresqlConnectionProperties $postgresqlConnectionProperties
   */
  public function setPostgresqlConnectionProperties(GoldengatePostgresqlConnectionProperties $postgresqlConnectionProperties)
  {
    $this->postgresqlConnectionProperties = $postgresqlConnectionProperties;
  }
  /**
   * @return GoldengatePostgresqlConnectionProperties
   */
  public function getPostgresqlConnectionProperties()
  {
    return $this->postgresqlConnectionProperties;
  }
  /**
   * Properties for a Redis connection.
   *
   * @param GoldengateRedisConnectionProperties $redisConnectionProperties
   */
  public function setRedisConnectionProperties(GoldengateRedisConnectionProperties $redisConnectionProperties)
  {
    $this->redisConnectionProperties = $redisConnectionProperties;
  }
  /**
   * @return GoldengateRedisConnectionProperties
   */
  public function getRedisConnectionProperties()
  {
    return $this->redisConnectionProperties;
  }
  /**
   * Optional. The routing method for the GoldengateConnection.
   *
   * Accepted values: GOLDENGATE_CONNECTION_ROUTING_METHOD_UNSPECIFIED,
   * SHARED_DEPLOYMENT_ENDPOINT, DEDICATED_ENDPOINT
   *
   * @param self::ROUTING_METHOD_* $routingMethod
   */
  public function setRoutingMethod($routingMethod)
  {
    $this->routingMethod = $routingMethod;
  }
  /**
   * @return self::ROUTING_METHOD_*
   */
  public function getRoutingMethod()
  {
    return $this->routingMethod;
  }
  /**
   * Properties for a Snowflake connection.
   *
   * @param GoldengateSnowflakeConnectionProperties $snowflakeConnectionProperties
   */
  public function setSnowflakeConnectionProperties(GoldengateSnowflakeConnectionProperties $snowflakeConnectionProperties)
  {
    $this->snowflakeConnectionProperties = $snowflakeConnectionProperties;
  }
  /**
   * @return GoldengateSnowflakeConnectionProperties
   */
  public function getSnowflakeConnectionProperties()
  {
    return $this->snowflakeConnectionProperties;
  }
  /**
   * Output only. The time the resource was last updated.
   *
   * @param string $updateTime
   */
  public function setUpdateTime($updateTime)
  {
    $this->updateTime = $updateTime;
  }
  /**
   * @return string
   */
  public function getUpdateTime()
  {
    return $this->updateTime;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoldengateConnectionProperties::class, 'Google_Service_OracleDatabase_GoldengateConnectionProperties');
