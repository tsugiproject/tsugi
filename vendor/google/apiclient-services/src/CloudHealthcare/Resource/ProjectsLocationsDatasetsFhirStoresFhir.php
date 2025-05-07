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

namespace Google\Service\CloudHealthcare\Resource;

use Google\Service\CloudHealthcare\HealthcareEmpty;
use Google\Service\CloudHealthcare\HttpBody;
use Google\Service\CloudHealthcare\SearchResourcesRequest;

/**
 * The "fhir" collection of methods.
 * Typical usage is:
 *  <code>
 *   $healthcareService = new Google\Service\CloudHealthcare(...);
 *   $fhir = $healthcareService->projects_locations_datasets_fhirStores_fhir;
 *  </code>
 */
class ProjectsLocationsDatasetsFhirStoresFhir extends \Google\Service\Resource
{
  /**
   * Creates a FHIR Binary resource. This method can be used to create a Binary
   * resource either by using one of the accepted FHIR JSON content types, or as a
   * raw data stream. If a resource is created with this method using the FHIR
   * content type this method's behavior is the same as
   * [`fhir.create`](https://cloud.google.com/healthcare-api/docs/reference/rest/v
   * 1/projects.locations.datasets.fhirStores.fhir/create). If a resource type
   * other than Binary is used in the request it's treated in the same way as non-
   * FHIR data (e.g., images, zip archives, pdf files, documents). When a non-FHIR
   * content type is used in the request, a Binary resource will be generated, and
   * the uploaded data will be stored in the `content` field (`DSTU2` and `STU3`),
   * or the `data` field (`R4`). The Binary resource's `contentType` will be
   * filled in using the value of the `Content-Type` header, and the
   * `securityContext` field (not present in `DSTU2`) will be populated from the
   * `X-Security-Context` header if it exists. At this time `securityContext` has
   * no special behavior in the Cloud Healthcare API. Note: the limit on data
   * ingested through this method is 1 GB. For best performance, use a non-FHIR
   * data type instead of wrapping the data in a Binary resource. Some of the
   * Healthcare API features, such as [exporting to
   * BigQuery](https://cloud.google.com/healthcare-api/docs/how-tos/fhir-export-
   * bigquery) or [Pub/Sub notifications](https://cloud.google.com/healthcare-
   * api/docs/fhir-
   * pubsub#behavior_when_a_fhir_resource_is_too_large_or_traffic_is_high) with
   * full resource content, do not support Binary resources that are larger than
   * 10 MB. In these cases the resource's `data` field will be omitted. Instead,
   * the "http://hl7.org/fhir/StructureDefinition/data-absent-reason" extension
   * will be present to indicate that including the data is `unsupported`. On
   * success, an empty `201 Created` response is returned. The newly created
   * resource's ID and version are returned in the Location header. Using `Prefer:
   * representation=resource` is not allowed for this method. The definition of
   * the Binary REST API can be found at https://hl7.org/fhir/binary.html#rest.
   * (fhir.BinaryCreate)
   *
   * @param string $parent Required. The name of the FHIR store this resource
   * belongs to.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function BinaryCreate($parent, HttpBody $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('Binary-create', [$params], HttpBody::class);
  }
  /**
   * Gets the contents of a FHIR Binary resource. This method can be used to
   * retrieve a Binary resource either by using the FHIR JSON mimetype as the
   * value for the Accept header, or as a raw data stream. If the FHIR Accept type
   * is used this method will return a Binary resource with the data
   * base64-encoded, regardless of how the resource was created. The resource data
   * can be retrieved in base64-decoded form if the Accept type of the request
   * matches the value of the resource's `contentType` field. The definition of
   * the Binary REST API can be found at https://hl7.org/fhir/binary.html#rest.
   * (fhir.BinaryRead)
   *
   * @param string $name Required. The name of the Binary resource to retrieve.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function BinaryRead($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('Binary-read', [$params], HttpBody::class);
  }
  /**
   * Updates the entire contents of a Binary resource. If the specified resource
   * does not exist and the FHIR store has enable_update_create set, creates the
   * resource with the client-specified ID. It is strongly advised not to include
   * or encode any sensitive data such as patient identifiers in client-specified
   * resource IDs. Those IDs are part of the FHIR resource path recorded in Cloud
   * Audit Logs and Pub/Sub notifications. Those IDs can also be contained in
   * reference fields within other resources. This method can be used to update a
   * Binary resource either by using one of the accepted FHIR JSON content types,
   * or as a raw data stream. If a resource is updated with this method using the
   * FHIR content type this method's behavior is the same as `update`. If a
   * resource type other than Binary is used in the request it will be treated in
   * the same way as non-FHIR data. When a non-FHIR content type is used in the
   * request, a Binary resource will be generated using the ID from the resource
   * path, and the uploaded data will be stored in the `content` field (`DSTU2`
   * and `STU3`), or the `data` field (`R4`). The Binary resource's `contentType`
   * will be filled in using the value of the `Content-Type` header, and the
   * `securityContext` field (not present in `DSTU2`) will be populated from the
   * `X-Security-Context` header if it exists. At this time `securityContext` has
   * no special behavior in the Cloud Healthcare API. Note: the limit on data
   * ingested through this method is 2 GB. For best performance, use a non-FHIR
   * data type instead of wrapping the data in a Binary resource. Some of the
   * Healthcare API features, such as [exporting to
   * BigQuery](https://cloud.google.com/healthcare-api/docs/how-tos/fhir-export-
   * bigquery) or [Pub/Sub notifications](https://cloud.google.com/healthcare-
   * api/docs/fhir-
   * pubsub#behavior_when_a_fhir_resource_is_too_large_or_traffic_is_high) with
   * full resource content, do not support Binary resources that are larger than
   * 10 MB. In these cases the resource's `data` field will be omitted. Instead,
   * the "http://hl7.org/fhir/StructureDefinition/data-absent-reason" extension
   * will be present to indicate that including the data is `unsupported`. On
   * success, an empty 200 OK response will be returned, or a 201 Created if the
   * resource did not exit. The resource's ID and version are returned in the
   * Location header. Using `Prefer: representation=resource` is not allowed for
   * this method. The definition of the Binary REST API can be found at
   * https://hl7.org/fhir/binary.html#rest. (fhir.BinaryUpdate)
   *
   * @param string $name Required. The name of the resource to update.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function BinaryUpdate($name, HttpBody $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('Binary-update', [$params], HttpBody::class);
  }
  /**
   * Gets the contents of a version (current or historical) of a FHIR Binary
   * resource by version ID. This method can be used to retrieve a Binary resource
   * version either by using the FHIR JSON mimetype as the value for the Accept
   * header, or as a raw data stream. If the FHIR Accept type is used this method
   * will return a Binary resource with the data base64-encoded, regardless of how
   * the resource version was created. The resource data can be retrieved in
   * base64-decoded form if the Accept type of the request matches the value of
   * the resource version's `contentType` field. The definition of the Binary REST
   * API can be found at https://hl7.org/fhir/binary.html#rest. (fhir.BinaryVread)
   *
   * @param string $name Required. The name of the Binary resource version to
   * retrieve.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function BinaryVread($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('Binary-vread', [$params], HttpBody::class);
  }
  /**
   * Returns the consent enforcement status of a single consent resource. On
   * success, the response body contains a JSON-encoded representation of a
   * `Parameters` (http://hl7.org/fhir/parameters.html) FHIR resource, containing
   * the current enforcement status. Does not support DSTU2.
   * (fhir.ConsentEnforcementStatus)
   *
   * @param string $name Required. The name of the consent resource to find
   * enforcement status, in the format `projects/{project_id}/locations/{location_
   * id}/datasets/{dataset_id}/fhirStores/{fhir_store_id}/fhir/Consent/{consent_id
   * }`
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function ConsentEnforcementStatus($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('Consent-enforcement-status', [$params], HttpBody::class);
  }
  /**
   * Returns the consent enforcement status of all consent resources for a
   * patient. On success, the response body contains a JSON-encoded representation
   * of a bundle of `Parameters` (http://hl7.org/fhir/parameters.html) FHIR
   * resources, containing the current enforcement status for each consent
   * resource of the patient. Does not support DSTU2.
   * (fhir.PatientConsentEnforcementStatus)
   *
   * @param string $name Required. The name of the patient to find enforcement
   * statuses, in the format `projects/{project_id}/locations/{location_id}/datase
   * ts/{dataset_id}/fhirStores/{fhir_store_id}/fhir/Patient/{patient_id}`
   * @param array $optParams Optional parameters.
   *
   * @opt_param int _count Optional. The maximum number of results on a page. If
   * not specified, 100 is used. May not be larger than 1000.
   * @opt_param string _page_token Optional. Used to retrieve the first, previous,
   * next, or last page of consent enforcement statuses when using pagination.
   * Value should be set to the value of `_page_token` set in next or previous
   * page links' URLs. Next and previous page are returned in the response
   * bundle's links field, where `link.relation` is "previous" or "next". Omit
   * `_page_token` if no previous request has been made.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function PatientConsentEnforcementStatus($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('Patient-consent-enforcement-status', [$params], HttpBody::class);
  }
  /**
   * Retrieves a Patient resource and resources related to that patient.
   * Implements the FHIR extended operation Patient-everything
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/patient-
   * operations.html#everything),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/patient-
   * operations.html#everything),
   * [R4](http://hl7.org/implement/standards/fhir/R4/patient-
   * operations.html#everything)). On success, the response body contains a JSON-
   * encoded representation of a `Bundle` resource of type `searchset`, containing
   * the results of the operation. Errors generated by the FHIR store contain a
   * JSON-encoded `OperationOutcome` resource describing the reason for the error.
   * If the request cannot be mapped to a valid API method on a FHIR store, a
   * generic GCP error might be returned instead. The resources in scope for the
   * response are: * The patient resource itself. * All the resources directly
   * referenced by the patient resource. * Resources directly referencing the
   * patient resource that meet the inclusion criteria. The inclusion criteria are
   * based on the membership rules in the patient compartment definition
   * ([DSTU2](http://hl7.org/fhir/DSTU2/compartment-patient.html),
   * [STU3](http://www.hl7.org/fhir/stu3/compartmentdefinition-patient.html),
   * [R4](http://hl7.org/fhir/R4/compartmentdefinition-patient.html)), which
   * details the eligible resource types and referencing search parameters. For
   * samples that show how to call `Patient-everything`, see [Getting all patient
   * compartment resources](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#getting_all_patient_compartment_resources).
   * (fhir.PatientEverything)
   *
   * @param string $name Required. Name of the `Patient` resource for which the
   * information is required.
   * @param array $optParams Optional parameters.
   *
   * @opt_param int _count Optional. Maximum number of resources in a page. If not
   * specified, 100 is used. May not be larger than 1000.
   * @opt_param string _page_token Used to retrieve the next or previous page of
   * results when using pagination. Set `_page_token` to the value of _page_token
   * set in next or previous page links' url. Next and previous page are returned
   * in the response bundle's links field, where `link.relation` is "previous" or
   * "next". Omit `_page_token` if no previous request has been made.
   * @opt_param string _since Optional. If provided, only resources updated after
   * this time are returned. The time uses the format YYYY-MM-
   * DDThh:mm:ss.sss+zz:zz. For example, `2015-02-07T13:28:17.239+02:00` or
   * `2017-01-01T00:00:00Z`. The time must be specified to the second and include
   * a time zone.
   * @opt_param string _type Optional. String of comma-delimited FHIR resource
   * types. If provided, only resources of the specified resource type(s) are
   * returned. Specifying multiple `_type` parameters isn't supported. For
   * example, the result of `_type=Observation&_type=Encounter` is undefined. Use
   * `_type=Observation,Encounter` instead.
   * @opt_param string end Optional. The response includes records prior to the
   * end date. The date uses the format YYYY-MM-DD. If no end date is provided,
   * all records subsequent to the start date are in scope.
   * @opt_param string start Optional. The response includes records subsequent to
   * the start date. The date uses the format YYYY-MM-DD. If no start date is
   * provided, all records prior to the end date are in scope.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function PatientEverything($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('Patient-everything', [$params], HttpBody::class);
  }
  /**
   * Deletes all the historical versions of a resource (excluding the current
   * version) from the FHIR store. To remove all versions of a resource, first
   * delete the current version and then call this method. This is not a FHIR
   * standard operation. For samples that show how to call `Resource-purge`, see
   * [Deleting historical versions of a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#deleting_historical_versions_of_a_fhir_resource).
   * (fhir.ResourcePurge)
   *
   * @param string $name Required. The name of the resource to purge.
   * @param array $optParams Optional parameters.
   * @return HealthcareEmpty
   * @throws \Google\Service\Exception
   */
  public function ResourcePurge($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('Resource-purge', [$params], HealthcareEmpty::class);
  }
  /**
   * Validates an input FHIR resource's conformance to its profiles and the
   * profiles configured on the FHIR store. Implements the FHIR extended operation
   * $validate ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/resource-
   * operations.html#validate),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/resource-
   * operations.html#validate), or
   * [R4](http://hl7.org/implement/standards/fhir/R4/resource-operation-
   * validate.html)). The request body must contain a JSON-encoded FHIR resource,
   * and the request headers must contain `Content-Type: application/fhir+json`.
   * The `Parameters` input syntax is not supported. The `profile` query parameter
   * can be used to request that the resource only be validated against a specific
   * profile. If a profile with the given URL cannot be found in the FHIR store
   * then an error is returned. Errors generated by validation contain a JSON-
   * encoded `OperationOutcome` resource describing the reason for the error. If
   * the request cannot be mapped to a valid API method on a FHIR store, a generic
   * GCP error might be returned instead. (fhir.ResourceValidate)
   *
   * @param string $parent Required. The name of the FHIR store that holds the
   * profiles being used for validation.
   * @param string $type Required. The FHIR resource type of the resource being
   * validated. For a complete list, see the FHIR Resource Index
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/resourcelist.html),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/resourcelist.html), or
   * [R4](http://hl7.org/implement/standards/fhir/R4/resourcelist.html)). Must
   * match the resource type in the provided content.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   *
   * @opt_param string profile Optional. The canonical URL of a profile that this
   * resource should be validated against. For example, to validate a Patient
   * resource against the US Core Patient profile this parameter would be
   * `http://hl7.org/fhir/us/core/StructureDefinition/us-core-patient`. A
   * StructureDefinition with this canonical URL must exist in the FHIR store.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function ResourceValidate($parent, $type, HttpBody $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'type' => $type, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('Resource-validate', [$params], HttpBody::class);
  }
  /**
   * Bulk exports all resources from the FHIR store to the specified destination.
   * Implements the FHIR implementation guide [system level
   * $export](https://build.fhir.org/ig/HL7/bulk-data/export.html#endpoint---
   * system-level-export. The following headers must be set in the request: *
   * `Accept`: specifies the format of the `OperationOutcome` response. Only
   * `application/fhir+json` is supported. * `Prefer`: specifies whether the
   * response is immediate or asynchronous. Must be to `respond-async` because
   * only asynchronous responses are supported. Specify the destination for the
   * server to write result files by setting the Cloud Storage location
   * bulk_export_gcs_destination on the FHIR store. URI of an existing Cloud
   * Storage directory where the server writes result files, in the format
   * gs://{bucket-id}/{path/to/destination/dir}. If there is no trailing slash,
   * the service appends one when composing the object path. The user is
   * responsible for creating the Cloud Storage bucket referenced. Supports the
   * following query parameters: * `_type`: string of comma-delimited FHIR
   * resource types. If provided, only the resources of the specified type(s) are
   * exported. * `_since`: if provided, only the resources that are updated after
   * the specified time are exported. * `_outputFormat`: optional, specify ndjson
   * to export data in NDJSON format. Exported file names use the format:
   * {export_id}_{resource_type}.ndjson. On success, the `Content-Location` header
   * of the response is set to a URL that the user can use to query the status of
   * the export. The URL is in the format: `projects/{project_id}/locations/{locat
   * ion_id}/datasets/{dataset_id}/fhirStores/{fhir_store_id}/operations/{export_i
   * d}`. See get-fhir-operation-status for more information. Errors generated by
   * the FHIR store contain a JSON-encoded `OperationOutcome` resource describing
   * the reason for the error. (fhir.bulkExport)
   *
   * @param string $name Required. The name of the FHIR store to export resources
   * from, in the format `projects/{project_id}/locations/{location_id}/datasets/{
   * dataset_id}/fhirStores/{fhir_store_id}`.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string _since Optional. If provided, only resources updated after
   * this time are exported. The time uses the format YYYY-MM-
   * DDThh:mm:ss.sss+zz:zz. For example, `2015-02-07T13:28:17.239+02:00` or
   * `2017-01-01T00:00:00Z`. The time must be specified to the second and include
   * a time zone.
   * @opt_param string _type Optional. String of comma-delimited FHIR resource
   * types. If provided, only resources of the specified resource type(s) are
   * exported.
   * @opt_param string outputFormat Optional. Output format of the export. This
   * field is optional and only `application/fhir+ndjson` is supported.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function bulkExport($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('bulk-export', [$params], HttpBody::class);
  }
  /**
   * Gets the FHIR capability statement ([STU3](http://hl7.org/implement/standards
   * /fhir/STU3/capabilitystatement.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/capabilitystatement.html)),
   * or the [conformance
   * statement](http://hl7.org/implement/standards/fhir/DSTU2/conformance.html) in
   * the DSTU2 case for the store, which contains a description of functionality
   * supported by the server. Implements the FHIR standard capabilities
   * interaction
   * ([STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#capabilities),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#capabilities)), or
   * the [conformance interaction](http://hl7.org/implement/standards/fhir/DSTU2/h
   * ttp.html#conformance) in the DSTU2 case. On success, the response body
   * contains a JSON-encoded representation of a `CapabilityStatement` resource.
   * (fhir.capabilities)
   *
   * @param string $name Required. Name of the FHIR store to retrieve the
   * capabilities for.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function capabilities($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('capabilities', [$params], HttpBody::class);
  }
  /**
   * Deletes a FHIR resource that match an identifier search query. Implements the
   * FHIR standard conditional delete interaction, limited to searching by
   * resource identifier. If multiple resources match, 412 Precondition Failed
   * error will be returned. Search term for identifier should be in the pattern
   * `identifier=system|value` or `identifier=value` - similar to the `search`
   * method on resources with a specific identifier. Note: Unless resource
   * versioning is disabled by setting the disable_resource_versioning flag on the
   * FHIR store, the deleted resource is moved to a history repository that can
   * still be retrieved through vread and related methods, unless they are removed
   * by the purge method. For samples that show how to call `conditionalDelete`,
   * see [Conditionally deleting a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#conditionally_deleting_a_fhir_resource). (fhir.conditionalDelete)
   *
   * @param string $parent Required. The name of the FHIR store this resource
   * belongs to.
   * @param string $type Required. The FHIR resource type to delete, such as
   * Patient or Observation. For a complete list, see the FHIR Resource Index
   * ([DSTU2](https://hl7.org/implement/standards/fhir/DSTU2/resourcelist.html),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/resourcelist.html),
   * [R4](https://hl7.org/implement/standards/fhir/R4/resourcelist.html)).
   * @param array $optParams Optional parameters.
   * @return HealthcareEmpty
   * @throws \Google\Service\Exception
   */
  public function conditionalDelete($parent, $type, $optParams = [])
  {
    $params = ['parent' => $parent, 'type' => $type];
    $params = array_merge($params, $optParams);
    return $this->call('conditionalDelete', [$params], HealthcareEmpty::class);
  }
  /**
   * If a resource is found with the identifier specified in the query parameters,
   * updates part of that resource by applying the operations specified in a [JSON
   * Patch](http://jsonpatch.com/) document. Implements the FHIR standard
   * conditional patch interaction, limited to searching by resource identifier.
   * DSTU2 doesn't define a conditional patch method, but the server supports it
   * in the same way it supports STU3. Search term for identifier should be in the
   * pattern `identifier=system|value` or `identifier=value` - similar to the
   * `search` method on resources with a specific identifier. If the search
   * criteria identify more than one match, the request returns a `412
   * Precondition Failed` error. The request body must contain a JSON Patch
   * document, and the request headers must contain `Content-Type:
   * application/json-patch+json`. On success, the response body contains a JSON-
   * encoded representation of the updated resource, including the server-assigned
   * version ID. Errors generated by the FHIR store contain a JSON-encoded
   * `OperationOutcome` resource describing the reason for the error. If the
   * request cannot be mapped to a valid API method on a FHIR store, a generic GCP
   * error might be returned instead. For samples that show how to call
   * `conditionalPatch`, see [Conditionally patching a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#conditionally_patching_a_fhir_resource). (fhir.conditionalPatch)
   *
   * @param string $parent Required. The name of the FHIR store this resource
   * belongs to.
   * @param string $type Required. The FHIR resource type to update, such as
   * Patient or Observation. For a complete list, see the FHIR Resource Index
   * ([DSTU2](https://hl7.org/implement/standards/fhir/DSTU2/resourcelist.html),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/resourcelist.html),
   * [R4](https://hl7.org/implement/standards/fhir/R4/resourcelist.html)).
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function conditionalPatch($parent, $type, HttpBody $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'type' => $type, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('conditionalPatch', [$params], HttpBody::class);
  }
  /**
   * If a resource is found with the identifier specified in the query parameters,
   * updates the entire contents of that resource. Implements the FHIR standard
   * conditional update interaction, limited to searching by resource identifier.
   * Search term for identifier should be in the pattern `identifier=system|value`
   * or `identifier=value` - similar to the `search` method on resources with a
   * specific identifier. If the search criteria identify more than one match, the
   * request returns a `412 Precondition Failed` error. If the search criteria
   * identify zero matches, and the supplied resource body contains an `id`, and
   * the FHIR store has enable_update_create set, creates the resource with the
   * client-specified ID. It is strongly advised not to include or encode any
   * sensitive data such as patient identifiers in client-specified resource IDs.
   * Those IDs are part of the FHIR resource path recorded in Cloud Audit Logs and
   * Pub/Sub notifications. Those IDs can also be contained in reference fields
   * within other resources. If the search criteria identify zero matches, and the
   * supplied resource body does not contain an `id`, the resource is created with
   * a server-assigned ID as per the create method. The request body must contain
   * a JSON-encoded FHIR resource, and the request headers must contain `Content-
   * Type: application/fhir+json`. On success, the response body contains a JSON-
   * encoded representation of the updated resource, including the server-assigned
   * version ID. Errors generated by the FHIR store contain a JSON-encoded
   * `OperationOutcome` resource describing the reason for the error. If the
   * request cannot be mapped to a valid API method on a FHIR store, a generic GCP
   * error might be returned instead. For samples that show how to call
   * `conditionalUpdate`, see [Conditionally updating a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#conditionally_updating_a_fhir_resource). (fhir.conditionalUpdate)
   *
   * @param string $parent Required. The name of the FHIR store this resource
   * belongs to.
   * @param string $type Required. The FHIR resource type to update, such as
   * Patient or Observation. For a complete list, see the FHIR Resource Index
   * ([DSTU2](https://hl7.org/implement/standards/fhir/DSTU2/resourcelist.html),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/resourcelist.html),
   * [R4](https://hl7.org/implement/standards/fhir/R4/resourcelist.html)). Must
   * match the resource type in the provided content.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function conditionalUpdate($parent, $type, HttpBody $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'type' => $type, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('conditionalUpdate', [$params], HttpBody::class);
  }
  /**
   * Creates a FHIR resource. Implements the FHIR standard create interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#create),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#create),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#create)), which
   * creates a new resource with a server-assigned resource ID. Also supports the
   * FHIR standard conditional create interaction
   * ([DSTU2](https://hl7.org/implement/standards/fhir/DSTU2/http.html#ccreate),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/http.html#ccreate),
   * [R4](https://hl7.org/implement/standards/fhir/R4/http.html#ccreate)),
   * specified by supplying an `If-None-Exist` header containing a FHIR search
   * query, limited to searching by resource identifier. If no resources match
   * this search query, the server processes the create operation as normal. When
   * using conditional create, the search term for identifier should be in the
   * pattern `identifier=system|value` or `identifier=value` - similar to the
   * `search` method on resources with a specific identifier. The request body
   * must contain a JSON-encoded FHIR resource, and the request headers must
   * contain `Content-Type: application/fhir+json`. On success, the response body
   * contains a JSON-encoded representation of the resource as it was created on
   * the server, including the server-assigned resource ID and version ID. Errors
   * generated by the FHIR store contain a JSON-encoded `OperationOutcome`
   * resource describing the reason for the error. If the request cannot be mapped
   * to a valid API method on a FHIR store, a generic GCP error might be returned
   * instead. For samples that show how to call `create`, see [Creating a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#creating_a_fhir_resource). (fhir.create)
   *
   * @param string $parent Required. The name of the FHIR store this resource
   * belongs to.
   * @param string $type Required. The FHIR resource type to create, such as
   * Patient or Observation. For a complete list, see the FHIR Resource Index
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/resourcelist.html),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/resourcelist.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/resourcelist.html)). Must
   * match the resource type in the provided content.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function create($parent, $type, HttpBody $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'type' => $type, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('create', [$params], HttpBody::class);
  }
  /**
   * Deletes a FHIR resource. Implements the FHIR standard delete interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#delete),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#delete),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#delete)). Note:
   * Unless resource versioning is disabled by setting the
   * disable_resource_versioning flag on the FHIR store, the deleted resources
   * will be moved to a history repository that can still be retrieved through
   * vread and related methods, unless they are removed by the purge method. For
   * samples that show how to call `delete`, see [Deleting a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#deleting_a_fhir_resource). (fhir.delete)
   *
   * @param string $name Required. The name of the resource to delete.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function delete($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('delete', [$params], HttpBody::class);
  }
  /**
   * Executes all the requests in the given Bundle. Implements the FHIR standard
   * batch/transaction interaction ([DSTU2](https://hl7.org/implement/standards/fh
   * ir/DSTU2/http.html#transaction),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/http.html#transaction),
   * [R4](https://hl7.org/implement/standards/fhir/R4/http.html#transaction)).
   * Supports all interactions within a bundle, except search. This method accepts
   * Bundles of type `batch` and `transaction`, processing them according to the
   * batch processing rules ([DSTU2](https://hl7.org/implement/standards/fhir/DSTU
   * 2/http.html#2.1.0.16.1),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/http.html#2.21.0.17.1),
   * [R4](https://hl7.org/implement/standards/fhir/R4/http.html#brules)) and
   * transaction processing rules ([DSTU2](https://hl7.org/implement/standards/fhi
   * r/DSTU2/http.html#2.1.0.16.2),
   * [STU3](https://hl7.org/implement/standards/fhir/STU3/http.html#2.21.0.17.2),
   * [R4](https://hl7.org/implement/standards/fhir/R4/http.html#trules)). The
   * request body must contain a JSON-encoded FHIR `Bundle` resource, and the
   * request headers must contain `Content-Type: application/fhir+json`. For a
   * batch bundle or a successful transaction, the response body contains a JSON-
   * encoded representation of a `Bundle` resource of type `batch-response` or
   * `transaction-response` containing one entry for each entry in the request,
   * with the outcome of processing the entry. In the case of an error for a
   * transaction bundle, the response body contains a JSON-encoded
   * `OperationOutcome` resource describing the reason for the error. If the
   * request cannot be mapped to a valid API method on a FHIR store, a generic GCP
   * error might be returned instead. This method checks permissions for each
   * request in the bundle. The `executeBundle` permission is required to call
   * this method, but you must also grant sufficient permissions to execute the
   * individual requests in the bundle. For example, if the bundle contains a
   * request to create a FHIR resource, the caller must also have been granted the
   * `healthcare.fhirResources.create` permission. You can use audit logs to view
   * the permissions for `executeBundle` and each request in the bundle. For more
   * information, see [Viewing Cloud Audit
   * logs](https://cloud.google.com/healthcare-api/docs/how-tos/audit-logging).
   * For samples that show how to call `executeBundle`, see [Managing FHIR
   * resources using FHIR bundles](https://cloud.google.com/healthcare/docs/how-
   * tos/fhir-bundles). (fhir.executeBundle)
   *
   * @param string $parent Required. Name of the FHIR store in which this bundle
   * will be executed.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function executeBundle($parent, HttpBody $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('executeBundle', [$params], HttpBody::class);
  }
  /**
   * Lists all the versions of a resource (including the current version and
   * deleted versions) from the FHIR store. Implements the per-resource form of
   * the FHIR standard history interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#history),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#history),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#history)). On
   * success, the response body contains a JSON-encoded representation of a
   * `Bundle` resource of type `history`, containing the version history sorted
   * from most recent to oldest versions. Errors generated by the FHIR store
   * contain a JSON-encoded `OperationOutcome` resource describing the reason for
   * the error. If the request cannot be mapped to a valid API method on a FHIR
   * store, a generic GCP error might be returned instead. For samples that show
   * how to call `history`, see [Listing FHIR resource
   * versions](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#listing_fhir_resource_versions). (fhir.history)
   *
   * @param string $name Required. The name of the resource to retrieve.
   * @param array $optParams Optional parameters.
   *
   * @opt_param string _at Only include resource versions that were current at
   * some point during the time period specified in the date time value. The date
   * parameter format is yyyy-mm-ddThh:mm:ss[Z|(+|-)hh:mm] Clients may specify any
   * of the following: * An entire year: `_at=2019` * An entire month:
   * `_at=2019-01` * A specific day: `_at=2019-01-20` * A specific second:
   * `_at=2018-12-31T23:59:58Z`
   * @opt_param int _count The maximum number of search results on a page. If not
   * specified, 100 is used. May not be larger than 1000.
   * @opt_param string _page_token Used to retrieve the first, previous, next, or
   * last page of resource versions when using pagination. Value should be set to
   * the value of `_page_token` set in next or previous page links' URLs. Next and
   * previous page are returned in the response bundle's links field, where
   * `link.relation` is "previous" or "next". Omit `_page_token` if no previous
   * request has been made.
   * @opt_param string _since Only include resource versions that were created at
   * or after the given instant in time. The instant in time uses the format YYYY-
   * MM-DDThh:mm:ss.sss+zz:zz (for example 2015-02-07T13:28:17.239+02:00 or
   * 2017-01-01T00:00:00Z). The time must be specified to the second and include a
   * time zone.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function history($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('history', [$params], HttpBody::class);
  }
  /**
   * Updates part of an existing resource by applying the operations specified in
   * a [JSON Patch](http://jsonpatch.com/) document. Implements the FHIR standard
   * patch interaction
   * ([STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#patch),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#patch)). DSTU2
   * doesn't define a patch method, but the server supports it in the same way it
   * supports STU3. The request body must contain a JSON Patch document, and the
   * request headers must contain `Content-Type: application/json-patch+json`. On
   * success, the response body contains a JSON-encoded representation of the
   * updated resource, including the server-assigned version ID. Errors generated
   * by the FHIR store contain a JSON-encoded `OperationOutcome` resource
   * describing the reason for the error. If the request cannot be mapped to a
   * valid API method on a FHIR store, a generic GCP error might be returned
   * instead. For samples that show how to call `patch`, see [Patching a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#patching_a_fhir_resource). (fhir.patch)
   *
   * @param string $name Required. The name of the resource to update.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function patch($name, HttpBody $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('patch', [$params], HttpBody::class);
  }
  /**
   * Gets the contents of a FHIR resource. Implements the FHIR standard read
   * interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#read),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#read),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#read)). Also
   * supports the FHIR standard conditional read interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#cread),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#cread),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#cread)) specified
   * by supplying an `If-Modified-Since` header with a date/time value or an `If-
   * None-Match` header with an ETag value. On success, the response body contains
   * a JSON-encoded representation of the resource. Errors generated by the FHIR
   * store contain a JSON-encoded `OperationOutcome` resource describing the
   * reason for the error. If the request cannot be mapped to a valid API method
   * on a FHIR store, a generic GCP error might be returned instead. For samples
   * that show how to call `read`, see [Getting a FHIR
   * resource](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#getting_a_fhir_resource). (fhir.read)
   *
   * @param string $name Required. The name of the resource to retrieve.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function read($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('read', [$params], HttpBody::class);
  }
  /**
   * Searches for resources in the given FHIR store according to criteria
   * specified as query parameters. Implements the FHIR standard search
   * interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#search),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#search),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#search)) using the
   * search semantics described in the FHIR Search specification
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/search.html),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/search.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/search.html)). Supports four
   * methods of search defined by the specification: * `GET [base]?[parameters]`
   * to search across all resources. * `GET [base]/[type]?[parameters]` to search
   * resources of a specified type. * `POST [base]/_search?[parameters]` as an
   * alternate form having the same semantics as the `GET` method across all
   * resources. * `POST [base]/[type]/_search?[parameters]` as an alternate form
   * having the same semantics as the `GET` method for the specified type. The
   * `GET` and `POST` methods do not support compartment searches. The `POST`
   * method does not support `application/x-www-form-urlencoded` search
   * parameters. On success, the response body contains a JSON-encoded
   * representation of a `Bundle` resource of type `searchset`, containing the
   * results of the search. Errors generated by the FHIR store contain a JSON-
   * encoded `OperationOutcome` resource describing the reason for the error. If
   * the request cannot be mapped to a valid API method on a FHIR store, a generic
   * GCP error might be returned instead. The server's capability statement,
   * retrieved through capabilities, indicates what search parameters are
   * supported on each FHIR resource. A list of all search parameters defined by
   * the specification can be found in the FHIR Search Parameter Registry
   * ([STU3](http://hl7.org/implement/standards/fhir/STU3/searchparameter-
   * registry.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/searchparameter-
   * registry.html)). FHIR search parameters for DSTU2 can be found on each
   * resource's definition page. Supported search modifiers: `:missing`, `:exact`,
   * `:contains`, `:text`, `:in`, `:not-in`, `:above`, `:below`, `:[type]`,
   * `:not`, and `recurse` (DSTU2 and STU3) or `:iterate` (R4). Supported search
   * result parameters: `_sort`, `_count`, `_include`, `_revinclude`,
   * `_summary=text`, `_summary=data`, and `_elements`. The maximum number of
   * search results returned defaults to 100, which can be overridden by the
   * `_count` parameter up to a maximum limit of 1000. The server might return
   * fewer resources than requested to prevent excessively large responses. If
   * there are additional results, the returned `Bundle` contains a link of
   * `relation` "next", which has a `_page_token` parameter for an opaque
   * pagination token that can be used to retrieve the next page. Resources with a
   * total size larger than 5MB or a field count larger than 50,000 might not be
   * fully searchable as the server might trim its generated search index in those
   * cases. Note: FHIR resources are indexed asynchronously, so there might be a
   * slight delay between the time a resource is created or changed, and the time
   * when the change reflects in search results. The only exception is resource
   * identifier data, which is indexed synchronously as a special index. As a
   * result, searching using resource identifier is not subject to indexing delay.
   * To use the special synchronous index, the search term for identifier should
   * be in the pattern `identifier=[system]|[value]` or `identifier=[value]`, and
   * any of the following search result parameters can be used: * `_count` *
   * `_include` * `_revinclude` * `_summary` * `_elements` If your query contains
   * any other search parameters, the standard asynchronous index will be used
   * instead. Note that searching against the special index is optimized for
   * resolving a small number of matches. The search isn't optimized if your
   * identifier search criteria matches a large number (i.e. more than 2,000) of
   * resources. For a search query that will match a large number of resources,
   * you can avoiding using the special synchronous index by including an
   * additional `_sort` parameter in your query. Use `_sort=-_lastUpdated` if you
   * want to keep the default sorting order. Note: The special synchronous
   * identifier index are currently disabled for DocumentReference and
   * DocumentManifest searches. For samples and detailed information, see
   * [Searching for FHIR resources](https://cloud.google.com/healthcare/docs/how-
   * tos/fhir-search) and [Advanced FHIR search
   * features](https://cloud.google.com/healthcare/docs/how-tos/fhir-advanced-
   * search). (fhir.search)
   *
   * @param string $parent Required. Name of the FHIR store to retrieve resources
   * from.
   * @param SearchResourcesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function search($parent, SearchResourcesRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('search', [$params], HttpBody::class);
  }
  /**
   * Searches for resources in the given FHIR store according to criteria
   * specified as query parameters. Implements the FHIR standard search
   * interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#search),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#search),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#search)) using the
   * search semantics described in the FHIR Search specification
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/search.html),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/search.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/search.html)). Supports four
   * methods of search defined by the specification: * `GET [base]?[parameters]`
   * to search across all resources. * `GET [base]/[type]?[parameters]` to search
   * resources of a specified type. * `POST [base]/_search?[parameters]` as an
   * alternate form having the same semantics as the `GET` method across all
   * resources. * `POST [base]/[type]/_search?[parameters]` as an alternate form
   * having the same semantics as the `GET` method for the specified type. The
   * `GET` and `POST` methods do not support compartment searches. The `POST`
   * method does not support `application/x-www-form-urlencoded` search
   * parameters. On success, the response body contains a JSON-encoded
   * representation of a `Bundle` resource of type `searchset`, containing the
   * results of the search. Errors generated by the FHIR store contain a JSON-
   * encoded `OperationOutcome` resource describing the reason for the error. If
   * the request cannot be mapped to a valid API method on a FHIR store, a generic
   * GCP error might be returned instead. The server's capability statement,
   * retrieved through capabilities, indicates what search parameters are
   * supported on each FHIR resource. A list of all search parameters defined by
   * the specification can be found in the FHIR Search Parameter Registry
   * ([STU3](http://hl7.org/implement/standards/fhir/STU3/searchparameter-
   * registry.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/searchparameter-
   * registry.html)). FHIR search parameters for DSTU2 can be found on each
   * resource's definition page. Supported search modifiers: `:missing`, `:exact`,
   * `:contains`, `:text`, `:in`, `:not-in`, `:above`, `:below`, `:[type]`,
   * `:not`, and `recurse` (DSTU2 and STU3) or `:iterate` (R4). Supported search
   * result parameters: `_sort`, `_count`, `_include`, `_revinclude`,
   * `_summary=text`, `_summary=data`, and `_elements`. The maximum number of
   * search results returned defaults to 100, which can be overridden by the
   * `_count` parameter up to a maximum limit of 1000. The server might return
   * fewer resources than requested to prevent excessively large responses. If
   * there are additional results, the returned `Bundle` contains a link of
   * `relation` "next", which has a `_page_token` parameter for an opaque
   * pagination token that can be used to retrieve the next page. Resources with a
   * total size larger than 5MB or a field count larger than 50,000 might not be
   * fully searchable as the server might trim its generated search index in those
   * cases. Note: FHIR resources are indexed asynchronously, so there might be a
   * slight delay between the time a resource is created or changed, and the time
   * when the change reflects in search results. The only exception is resource
   * identifier data, which is indexed synchronously as a special index. As a
   * result, searching using resource identifier is not subject to indexing delay.
   * To use the special synchronous index, the search term for identifier should
   * be in the pattern `identifier=[system]|[value]` or `identifier=[value]`, and
   * any of the following search result parameters can be used: * `_count` *
   * `_include` * `_revinclude` * `_summary` * `_elements` If your query contains
   * any other search parameters, the standard asynchronous index will be used
   * instead. Note that searching against the special index is optimized for
   * resolving a small number of matches. The search isn't optimized if your
   * identifier search criteria matches a large number (i.e. more than 2,000) of
   * resources. For a search query that will match a large number of resources,
   * you can avoiding using the special synchronous index by including an
   * additional `_sort` parameter in your query. Use `_sort=-_lastUpdated` if you
   * want to keep the default sorting order. Note: The special synchronous
   * identifier index are currently disabled for DocumentReference and
   * DocumentManifest searches. For samples and detailed information, see
   * [Searching for FHIR resources](https://cloud.google.com/healthcare/docs/how-
   * tos/fhir-search) and [Advanced FHIR search
   * features](https://cloud.google.com/healthcare/docs/how-tos/fhir-advanced-
   * search). (fhir.searchType)
   *
   * @param string $parent Required. Name of the FHIR store to retrieve resources
   * from.
   * @param string $resourceType Optional. The FHIR resource type to search, such
   * as Patient or Observation. For a complete list, see the FHIR Resource Index
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/resourcelist.html),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/resourcelist.html),
   * [R4](http://hl7.org/implement/standards/fhir/R4/resourcelist.html)).
   * @param SearchResourcesRequest $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function searchType($parent, $resourceType, SearchResourcesRequest $postBody, $optParams = [])
  {
    $params = ['parent' => $parent, 'resourceType' => $resourceType, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('search-type', [$params], HttpBody::class);
  }
  /**
   * Updates the entire contents of a resource. Implements the FHIR standard
   * update interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#update),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#update),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#update)). If the
   * specified resource does not exist and the FHIR store has enable_update_create
   * set, creates the resource with the client-specified ID. It is strongly
   * advised not to include or encode any sensitive data such as patient
   * identifiers in client-specified resource IDs. Those IDs are part of the FHIR
   * resource path recorded in Cloud Audit Logs and Pub/Sub notifications. Those
   * IDs can also be contained in reference fields within other resources. The
   * request body must contain a JSON-encoded FHIR resource, and the request
   * headers must contain `Content-Type: application/fhir+json`. The resource must
   * contain an `id` element having an identical value to the ID in the REST path
   * of the request. On success, the response body contains a JSON-encoded
   * representation of the updated resource, including the server-assigned version
   * ID. Errors generated by the FHIR store contain a JSON-encoded
   * `OperationOutcome` resource describing the reason for the error. If the
   * request cannot be mapped to a valid API method on a FHIR store, a generic GCP
   * error might be returned instead. For samples that show how to call `update`,
   * see [Updating a FHIR resource](https://cloud.google.com/healthcare/docs/how-
   * tos/fhir-resources#updating_a_fhir_resource). (fhir.update)
   *
   * @param string $name Required. The name of the resource to update.
   * @param HttpBody $postBody
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function update($name, HttpBody $postBody, $optParams = [])
  {
    $params = ['name' => $name, 'postBody' => $postBody];
    $params = array_merge($params, $optParams);
    return $this->call('update', [$params], HttpBody::class);
  }
  /**
   * Gets the contents of a version (current or historical) of a FHIR resource by
   * version ID. Implements the FHIR standard vread interaction
   * ([DSTU2](http://hl7.org/implement/standards/fhir/DSTU2/http.html#vread),
   * [STU3](http://hl7.org/implement/standards/fhir/STU3/http.html#vread),
   * [R4](http://hl7.org/implement/standards/fhir/R4/http.html#vread)). On
   * success, the response body contains a JSON-encoded representation of the
   * resource. Errors generated by the FHIR store contain a JSON-encoded
   * `OperationOutcome` resource describing the reason for the error. If the
   * request cannot be mapped to a valid API method on a FHIR store, a generic GCP
   * error might be returned instead. For samples that show how to call `vread`,
   * see [Retrieving a FHIR resource
   * version](https://cloud.google.com/healthcare/docs/how-tos/fhir-
   * resources#retrieving_a_fhir_resource_version). (fhir.vread)
   *
   * @param string $name Required. The name of the resource version to retrieve.
   * @param array $optParams Optional parameters.
   * @return HttpBody
   * @throws \Google\Service\Exception
   */
  public function vread($name, $optParams = [])
  {
    $params = ['name' => $name];
    $params = array_merge($params, $optParams);
    return $this->call('vread', [$params], HttpBody::class);
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(ProjectsLocationsDatasetsFhirStoresFhir::class, 'Google_Service_CloudHealthcare_Resource_ProjectsLocationsDatasetsFhirStoresFhir');
