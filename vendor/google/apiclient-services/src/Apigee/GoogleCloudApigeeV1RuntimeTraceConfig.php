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

namespace Google\Service\Apigee;

class GoogleCloudApigeeV1RuntimeTraceConfig extends \Google\Collection
{
  /**
   * Exporter unspecified
   */
  public const EXPORTER_EXPORTER_UNSPECIFIED = 'EXPORTER_UNSPECIFIED';
  /**
   * Exports events to Jaeger. Compatible with OpenCensus protocol.
   */
  public const EXPORTER_JAEGER = 'JAEGER';
  /**
   * Exports events to Cloud Trace. Compatible with OpenCensus protocol.
   */
  public const EXPORTER_CLOUD_TRACE = 'CLOUD_TRACE';
  /**
   * OpenTelemetry Collector. Compatible with OpenTelemetry protocol.
   */
  public const EXPORTER_OPEN_TELEMETRY_COLLECTOR = 'OPEN_TELEMETRY_COLLECTOR';
  /**
   * Exports events to Cloud Trace. Compatible with OpenTelemetry protocol.
   */
  public const EXPORTER_OPEN_TELEMETRY_CLOUD_TRACE = 'OPEN_TELEMETRY_CLOUD_TRACE';
  /**
   * Protocol unspecified. Defaults to OPEN_CENSUS.
   */
  public const TRACE_PROTOCOL_TRACE_PROTOCOL_UNSPECIFIED = 'TRACE_PROTOCOL_UNSPECIFIED';
  /**
   * Uses OpenCensus protocol.
   */
  public const TRACE_PROTOCOL_OPEN_CENSUS = 'OPEN_CENSUS';
  /**
   * Uses OpenTelemetry Protocol (OTLP).
   */
  public const TRACE_PROTOCOL_OTLP = 'OTLP';
  protected $collection_key = 'overrides';
  /**
   * Endpoint of the exporter.
   *
   * @var string
   */
  public $endpoint;
  /**
   * Exporter that is used to view the distributed trace captured using
   * OpenCensus. An exporter sends traces to any backend that is capable of
   * consuming them. Recorded spans can be exported by registered exporters.
   *
   * @var string
   */
  public $exporter;
  /**
   * Name of the trace config in the following format:
   * `organizations/{org}/environment/{env}/traceConfig`
   *
   * @var string
   */
  public $name;
  /**
   * Optional. If `true`, the runtime uses OpenTelemetry Protocol (OTLP) to send
   * trace data. Configuration Requirements (if
   * `open_telemetry_protocol_enabled` is `true`): - Allowed `Exporter`s:
   * `CLOUD_TRACE` or `OPEN_TELEMETRY_COLLECTOR`. - If `Exporter` is
   * `OPEN_TELEMETRY_COLLECTOR`: - `endpoint` refers to a valid OTLP collector
   * URL. - If `Exporter` is `CLOUD_TRACE`: - `endpoint` refers to a valid
   * project ID Deprecated: Use trace_protocol instead.
   *
   * @deprecated
   * @var bool
   */
  public $openTelemetryProtocolEnabled;
  protected $overridesType = GoogleCloudApigeeV1RuntimeTraceConfigOverride::class;
  protected $overridesDataType = 'array';
  /**
   * The timestamp that the revision was created or updated.
   *
   * @var string
   */
  public $revisionCreateTime;
  /**
   * Revision number which can be used by the runtime to detect if the trace
   * config has changed between two versions.
   *
   * @var string
   */
  public $revisionId;
  protected $samplingConfigType = GoogleCloudApigeeV1RuntimeTraceSamplingConfig::class;
  protected $samplingConfigDataType = '';
  /**
   * Optional. The trace protocol to use.
   *
   * @var string
   */
  public $traceProtocol;

  /**
   * Endpoint of the exporter.
   *
   * @param string $endpoint
   */
  public function setEndpoint($endpoint)
  {
    $this->endpoint = $endpoint;
  }
  /**
   * @return string
   */
  public function getEndpoint()
  {
    return $this->endpoint;
  }
  /**
   * Exporter that is used to view the distributed trace captured using
   * OpenCensus. An exporter sends traces to any backend that is capable of
   * consuming them. Recorded spans can be exported by registered exporters.
   *
   * Accepted values: EXPORTER_UNSPECIFIED, JAEGER, CLOUD_TRACE,
   * OPEN_TELEMETRY_COLLECTOR, OPEN_TELEMETRY_CLOUD_TRACE
   *
   * @param self::EXPORTER_* $exporter
   */
  public function setExporter($exporter)
  {
    $this->exporter = $exporter;
  }
  /**
   * @return self::EXPORTER_*
   */
  public function getExporter()
  {
    return $this->exporter;
  }
  /**
   * Name of the trace config in the following format:
   * `organizations/{org}/environment/{env}/traceConfig`
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
   * Optional. If `true`, the runtime uses OpenTelemetry Protocol (OTLP) to send
   * trace data. Configuration Requirements (if
   * `open_telemetry_protocol_enabled` is `true`): - Allowed `Exporter`s:
   * `CLOUD_TRACE` or `OPEN_TELEMETRY_COLLECTOR`. - If `Exporter` is
   * `OPEN_TELEMETRY_COLLECTOR`: - `endpoint` refers to a valid OTLP collector
   * URL. - If `Exporter` is `CLOUD_TRACE`: - `endpoint` refers to a valid
   * project ID Deprecated: Use trace_protocol instead.
   *
   * @deprecated
   * @param bool $openTelemetryProtocolEnabled
   */
  public function setOpenTelemetryProtocolEnabled($openTelemetryProtocolEnabled)
  {
    $this->openTelemetryProtocolEnabled = $openTelemetryProtocolEnabled;
  }
  /**
   * @deprecated
   * @return bool
   */
  public function getOpenTelemetryProtocolEnabled()
  {
    return $this->openTelemetryProtocolEnabled;
  }
  /**
   * List of trace configuration overrides for spicific API proxies.
   *
   * @param GoogleCloudApigeeV1RuntimeTraceConfigOverride[] $overrides
   */
  public function setOverrides($overrides)
  {
    $this->overrides = $overrides;
  }
  /**
   * @return GoogleCloudApigeeV1RuntimeTraceConfigOverride[]
   */
  public function getOverrides()
  {
    return $this->overrides;
  }
  /**
   * The timestamp that the revision was created or updated.
   *
   * @param string $revisionCreateTime
   */
  public function setRevisionCreateTime($revisionCreateTime)
  {
    $this->revisionCreateTime = $revisionCreateTime;
  }
  /**
   * @return string
   */
  public function getRevisionCreateTime()
  {
    return $this->revisionCreateTime;
  }
  /**
   * Revision number which can be used by the runtime to detect if the trace
   * config has changed between two versions.
   *
   * @param string $revisionId
   */
  public function setRevisionId($revisionId)
  {
    $this->revisionId = $revisionId;
  }
  /**
   * @return string
   */
  public function getRevisionId()
  {
    return $this->revisionId;
  }
  /**
   * Trace configuration for all API proxies in an environment.
   *
   * @param GoogleCloudApigeeV1RuntimeTraceSamplingConfig $samplingConfig
   */
  public function setSamplingConfig(GoogleCloudApigeeV1RuntimeTraceSamplingConfig $samplingConfig)
  {
    $this->samplingConfig = $samplingConfig;
  }
  /**
   * @return GoogleCloudApigeeV1RuntimeTraceSamplingConfig
   */
  public function getSamplingConfig()
  {
    return $this->samplingConfig;
  }
  /**
   * Optional. The trace protocol to use.
   *
   * Accepted values: TRACE_PROTOCOL_UNSPECIFIED, OPEN_CENSUS, OTLP
   *
   * @param self::TRACE_PROTOCOL_* $traceProtocol
   */
  public function setTraceProtocol($traceProtocol)
  {
    $this->traceProtocol = $traceProtocol;
  }
  /**
   * @return self::TRACE_PROTOCOL_*
   */
  public function getTraceProtocol()
  {
    return $this->traceProtocol;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudApigeeV1RuntimeTraceConfig::class, 'Google_Service_Apigee_GoogleCloudApigeeV1RuntimeTraceConfig');
