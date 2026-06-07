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

namespace Google\Service\FirebaseDataConnect;

class PostgreSql extends \Google\Model
{
  /**
   * Unspecified SQL schema migration.
   */
  public const SCHEMA_MIGRATION_SQL_SCHEMA_MIGRATION_UNSPECIFIED = 'SQL_SCHEMA_MIGRATION_UNSPECIFIED';
  /**
   * Waits for the Cloud SQL instance to be provisioned and automatically
   * creates necessary SQL resources (tables, columns, etc.) to match the
   * desired FDC schema. This operation is strictly additive and executes as a
   * Long-Running Operation during provisioning. Rejects migrations on a non-
   * empty existing SQL schema.
   */
  public const SCHEMA_MIGRATION_MIGRATE_COMPATIBLE = 'MIGRATE_COMPATIBLE';
  /**
   * Unspecified SQL schema validation. Defaults to STRICT.
   */
  public const SCHEMA_VALIDATION_SQL_SCHEMA_VALIDATION_UNSPECIFIED = 'SQL_SCHEMA_VALIDATION_UNSPECIFIED';
  /**
   * Skips SQL schema validation. Deployment succeeds even if the database is
   * pending provisioning, unavailable, or incompatible. Under NONE, newly
   * created services route requests to a temporary ephemeral database (in-
   * memory emulation) so the API can be tested immediately. Ephemeral data
   * expires after 24 hours unless successfully validated or migrated to a
   * linked database.
   */
  public const SCHEMA_VALIDATION_NONE = 'NONE';
  /**
   * Connects to the SQL database and validates that the SQL DDL matches the FDC
   * schema exactly. Any discrepancies (extra or missing tables/columns) result
   * in a FAILED_PRECONDITION error with required SQL diffs. Recommended for
   * greenfield projects to ensure full schema consistency.
   */
  public const SCHEMA_VALIDATION_STRICT = 'STRICT';
  /**
   * Connects to the SQL database and validates that it contains all the SQL
   * resources required by the FDC schema. Succeeds even if the database
   * contains additional tables or columns not used by FDC. Suitable when
   * sharing a database with other tools or legacy applications.
   */
  public const SCHEMA_VALIDATION_COMPATIBLE = 'COMPATIBLE';
  protected $cloudSqlType = CloudSqlInstance::class;
  protected $cloudSqlDataType = '';
  /**
   * Required. Name of the PostgreSQL database.
   *
   * @var string
   */
  public $database;
  /**
   * Output only. Ephemeral is true if this SQL Connect service is served from
   * temporary in-memory emulation of Postgres. While Cloud SQL is being
   * provisioned, the SQL Connect service provides the ephemeral service to help
   * developers get started. Once the Cloud SQL is provisioned, SQL Connect
   * service will transfer its data on a best-effort basis to the Cloud SQL
   * instance. WARNING: Ephemeral data sources will expire after 24 hour. The
   * data will be lost if they aren't transferred to the Cloud SQL instance.
   * WARNING: When `ephemeral=true`, mutations to the database are not
   * guaranteed to be durably persisted, even if an OK status code is returned.
   * All or parts of the data may be lost or reverted to earlier versions.
   *
   * @var bool
   */
  public $ephemeral;
  /**
   * Optional. User-configured PostgreSQL schema. Defaults to "public" if not
   * specified.
   *
   * @var string
   */
  public $schema;
  /**
   * Optional. Configure how to perform automatic PostgreSQL schema migration
   * before deploying the FDC schema. This is an additive-only operation.
   *
   * @var string
   */
  public $schemaMigration;
  /**
   * Optional. Configure how much PostgreSQL schema validation to perform
   * against the live database before deploying the FDC schema.
   *
   * @var string
   */
  public $schemaValidation;
  /**
   * No Postgres data source is linked. If set, don't allow `database` and
   * `schema_validation` to be configured.
   *
   * @deprecated
   * @var bool
   */
  public $unlinked;

  /**
   * Cloud SQL configurations.
   *
   * @param CloudSqlInstance $cloudSql
   */
  public function setCloudSql(CloudSqlInstance $cloudSql)
  {
    $this->cloudSql = $cloudSql;
  }
  /**
   * @return CloudSqlInstance
   */
  public function getCloudSql()
  {
    return $this->cloudSql;
  }
  /**
   * Required. Name of the PostgreSQL database.
   *
   * @param string $database
   */
  public function setDatabase($database)
  {
    $this->database = $database;
  }
  /**
   * @return string
   */
  public function getDatabase()
  {
    return $this->database;
  }
  /**
   * Output only. Ephemeral is true if this SQL Connect service is served from
   * temporary in-memory emulation of Postgres. While Cloud SQL is being
   * provisioned, the SQL Connect service provides the ephemeral service to help
   * developers get started. Once the Cloud SQL is provisioned, SQL Connect
   * service will transfer its data on a best-effort basis to the Cloud SQL
   * instance. WARNING: Ephemeral data sources will expire after 24 hour. The
   * data will be lost if they aren't transferred to the Cloud SQL instance.
   * WARNING: When `ephemeral=true`, mutations to the database are not
   * guaranteed to be durably persisted, even if an OK status code is returned.
   * All or parts of the data may be lost or reverted to earlier versions.
   *
   * @param bool $ephemeral
   */
  public function setEphemeral($ephemeral)
  {
    $this->ephemeral = $ephemeral;
  }
  /**
   * @return bool
   */
  public function getEphemeral()
  {
    return $this->ephemeral;
  }
  /**
   * Optional. User-configured PostgreSQL schema. Defaults to "public" if not
   * specified.
   *
   * @param string $schema
   */
  public function setSchema($schema)
  {
    $this->schema = $schema;
  }
  /**
   * @return string
   */
  public function getSchema()
  {
    return $this->schema;
  }
  /**
   * Optional. Configure how to perform automatic PostgreSQL schema migration
   * before deploying the FDC schema. This is an additive-only operation.
   *
   * Accepted values: SQL_SCHEMA_MIGRATION_UNSPECIFIED, MIGRATE_COMPATIBLE
   *
   * @param self::SCHEMA_MIGRATION_* $schemaMigration
   */
  public function setSchemaMigration($schemaMigration)
  {
    $this->schemaMigration = $schemaMigration;
  }
  /**
   * @return self::SCHEMA_MIGRATION_*
   */
  public function getSchemaMigration()
  {
    return $this->schemaMigration;
  }
  /**
   * Optional. Configure how much PostgreSQL schema validation to perform
   * against the live database before deploying the FDC schema.
   *
   * Accepted values: SQL_SCHEMA_VALIDATION_UNSPECIFIED, NONE, STRICT,
   * COMPATIBLE
   *
   * @param self::SCHEMA_VALIDATION_* $schemaValidation
   */
  public function setSchemaValidation($schemaValidation)
  {
    $this->schemaValidation = $schemaValidation;
  }
  /**
   * @return self::SCHEMA_VALIDATION_*
   */
  public function getSchemaValidation()
  {
    return $this->schemaValidation;
  }
  /**
   * No Postgres data source is linked. If set, don't allow `database` and
   * `schema_validation` to be configured.
   *
   * @deprecated
   * @param bool $unlinked
   */
  public function setUnlinked($unlinked)
  {
    $this->unlinked = $unlinked;
  }
  /**
   * @deprecated
   * @return bool
   */
  public function getUnlinked()
  {
    return $this->unlinked;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(PostgreSql::class, 'Google_Service_FirebaseDataConnect_PostgreSql');
