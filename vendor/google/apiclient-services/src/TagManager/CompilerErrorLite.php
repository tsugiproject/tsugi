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

namespace Google\Service\TagManager;

class CompilerErrorLite extends \Google\Model
{
  /**
   * Unknown error type. Place holder for the enum's default value; not valid.
   */
  public const ERROR_TYPE_unknownErrorType = 'unknownErrorType';
  /**
   * There are multiple auto-event instances with the same name. parent - N
   * EntityKeys each with their auto_event_key populated.
   */
  public const ERROR_TYPE_duplicateAutoEventName = 'duplicateAutoEventName';
  /**
   * There are multiple conditions with the same id. parent - N EntityKeys each
   * with their condition_key populated.
   */
  public const ERROR_TYPE_duplicateConditionId = 'duplicateConditionId';
  /**
   * There are multiple default macros with the same name. parent - N EntityKeys
   * each with their macro_key populated. context - The macro name.
   */
  public const ERROR_TYPE_duplicateDefaultMacro = 'duplicateDefaultMacro';
  /**
   * There are multiple tag instances with the same name. parent - N EntityKeys
   * each with their tag_key populated.
   */
  public const ERROR_TYPE_duplicateTagName = 'duplicateTagName';
  /**
   * An internal compiler invariant was broken. parent - 1 EntityKey which may
   * have macro_key, condition_key, or tag_key populated. It's also possible
   * that no keys are populated. context - An internal string (shouldn't be
   * shown to the user) describing the problem.
   */
  public const ERROR_TYPE_internalCompilerError = 'internalCompilerError';
  /**
   * An macro instance key was invalid e.g. foo#bar parent - 1 EntityKey with
   * one of the following populated: macro_key, condition_key, tag_key. context
   * - The string of the invalid name.
   */
  public const ERROR_TYPE_invalidMacroKey = 'invalidMacroKey';
  /**
   * Wrong number of args passed to a predicate. parent - 1 EntityKey with its
   * condition_key populated.
   */
  public const ERROR_TYPE_invalidNumberPredicateArgs = 'invalidNumberPredicateArgs';
  /**
   * Macro format was invalid e.g. foo#bar parent - 1 EntityKey with one of the
   * following populated: condition_key, tag_key. context - The full string of
   * the invalid macro and surrounding literals.
   */
  public const ERROR_TYPE_invalidMacroFormat = 'invalidMacroFormat';
  /**
   * A macro name was referenced instead of a macro key. parent - 1 EntityKey
   * with one of the following populated: condition_key, tag_key. context - The
   * name of the invalid macro name.
   */
  public const ERROR_TYPE_invalidMacroNameReference = 'invalidMacroNameReference';
  /**
   * Macro had an invalid parameter. This could be anything from a parameter
   * being a complex type or a macro parameter containing a macro reference.
   * parent - 1 EntityKey with its macro_key populated. context - The value of
   * the offending parameter if it is string-able.
   */
  public const ERROR_TYPE_invalidMacroParameter = 'invalidMacroParameter';
  /**
   * Usage context of a container was invalid. Currently, this error can occur
   * when a container context specifies both web and mobile. The UI shouldn't
   * allowed creating such a mixed container. parent - not set context - The
   * description of the context
   */
  public const ERROR_TYPE_invalidUsageContext = 'invalidUsageContext';
  /**
   * Contents of an Regex predicate had an invalid pattern. parent - 1 EntityKey
   * with one of the following populated: condition_key. context - The offending
   * pattern.
   */
  public const ERROR_TYPE_invalidRegex = 'invalidRegex';
  /**
   * There was a macro whose resolution would depend on itself. parent - N
   * EntityKeys each with their macro_key, trigger_key, or condition_key
   * populated. This represents the macro cycle. context - Empty.
   */
  public const ERROR_TYPE_macroCycle = 'macroCycle';
  /**
   * A condition id was used which doesn't exist in the conditions list. parent
   * - 1 EntityKey with its tag_key populated. context - The integer id of the
   * missing conditional.
   */
  public const ERROR_TYPE_unknownConditionId = 'unknownConditionId';
  /**
   * A macro name was used which doesn't exist in the macro list. parent - 1
   * EntityKey with one of the following populated: condition_key, tag_key.
   * context - The name of the missing macro instance.
   */
  public const ERROR_TYPE_unknownMacroInstance = 'unknownMacroInstance';
  /**
   * User-provided escaping inappropriate for the context in which it is used.
   * parent - 1 EntityKey with one of the following populated: macro_key,
   * condition_key, tag_key. context - Empty.
   */
  public const ERROR_TYPE_invalidManualEscaping = 'invalidManualEscaping';
  /**
   * Auto-escaped content is not valid HTML, CSS or JavaScript. parent - 1
   * EntityKey with one of the following populated: tag_key. context - Empty.
   */
  public const ERROR_TYPE_invalidHtmlCssJs = 'invalidHtmlCssJs';
  /**
   * Macro reference found inside an HTML, CSS or JavaScript comment. parent - 1
   * EntityKey with one of the following populated: tag_key. context - Empty.
   */
  public const ERROR_TYPE_macroInCommentsError = 'macroInCommentsError';
  /**
   * Contents of an HTML script tag could not be compiled by JsCompiler. parent
   * - 1 EntityKey with one of the following populated: tag_key. context -
   * Empty.
   */
  public const ERROR_TYPE_jsCompilerError = 'jsCompilerError';
  /**
   * Contents of a ConfigurationValue script tag could not be parsed by the JSON
   * parser. parent - 1 EntityKey with one of the following populated: tag_key.
   * context - Empty.
   */
  public const ERROR_TYPE_jsonError = 'jsonError';
  /**
   * Tag had an invalid parameter. This could be anything from a parameter being
   * a complex type or a macro parameter containing a macro reference. parent -
   * 1 EntityKey with its tag_key populated. context - The value of the
   * offending parameter if it is string-able.
   */
  public const ERROR_TYPE_invalidTagParameter = 'invalidTagParameter';
  /**
   * An arbitrary HTML tag contains a piece of javascript with too many
   * contiguous non-whitespace characters (e.g. a long array with no spaces
   * between elements or an extremely long variable name).
   */
  public const ERROR_TYPE_javascriptTooLong = 'javascriptTooLong';
  /**
   * A tag name was used which doesn't exist in the input tag list. parent - 1
   * EntityKey with tag_key populated. context - The name of the missing tag
   * instance.
   */
  public const ERROR_TYPE_unknownTagInstance = 'unknownTagInstance';
  /**
   * A tag name was used which doesn't exist in the compiled tag list. This
   * happens if a tag is dependent on another tag, but the other tag has no
   * rules attached to it and is pruned in the compiler. parent - 1 EntityKey
   * with tag_key populated. context - The name of the missing tag instance.
   */
  public const ERROR_TYPE_invalidTagReference = 'invalidTagReference';
  /**
   * A trigger id was used which doesn't exist in the triggers list. parent - 1
   * EntityKey parent of the unknown trigger. context - The id of the missing
   * trigger instance.
   */
  public const ERROR_TYPE_unknownTriggerId = 'unknownTriggerId';
  /**
   * A trigger of type custom trigger was created that doesn't include a custom
   * event filter. parent - 1 EntityKey with its trigger_key populated. context
   * - Empty.
   */
  public const ERROR_TYPE_customTriggerMissingEventFilter = 'customTriggerMissingEventFilter';
  /**
   * There are multiple triggers with the same id. parent - N EntityKeys each
   * with their trigger_key populated. context - Empty.
   */
  public const ERROR_TYPE_duplicateTriggerId = 'duplicateTriggerId';
  /**
   * There is an unknown or unsupported trigger type. parent - 1 EntityKey with
   * its trigger_key populated. context - The trigger type.
   */
  public const ERROR_TYPE_unsupportedTriggerType = 'unsupportedTriggerType';
  /**
   * A trigger has an invalid parameter. parent - 1 EntityKey with its
   * trigger_key populated. context - The invalid field.
   */
  public const ERROR_TYPE_invalidTriggerParameter = 'invalidTriggerParameter';
  /**
   * There are multiple experiments with the same id. parent - N EntityKeys each
   * with their experiment_key populated. context - Empty.
   */
  public const ERROR_TYPE_duplicateExperimentId = 'duplicateExperimentId';
  /**
   * Contents of a pixie tag/macro could not be compiled by Pixie Parser. parent
   * - 1 EntityKey with one of the following populated: tag_key, macro_key.
   * context - Empty.
   */
  public const ERROR_TYPE_pixieCompilerError = 'pixieCompilerError';
  /**
   * The macro cannot be resolved at server side. parent - 1 EntityKey
   * indicating the entity in which this macro is used. context - The macro
   * name.
   */
  public const ERROR_TYPE_macroNotServerSideResolvable = 'macroNotServerSideResolvable';
  /**
   * The trigger cannot be used in blocking predicates (i.e. only All/Some pages
   * triggers work for AMP, NS etc.) parent - 1 EntityKey indicating the tag in
   * which this trigger is used. context - The trigger name.
   */
  public const ERROR_TYPE_invalidBlockingTrigger = 'invalidBlockingTrigger';
  /**
   * A line in the input text is too long.
   */
  public const ERROR_TYPE_lineTooLong = 'lineTooLong';
  /**
   * The value of a SELECT parameter in a vendor template instance does not
   * point to an allowed vendor template instance. For details, see the
   * documentation of the typesInSelect property in a vendor template. parent -
   * 1 EntityKey indicating the tag/macro with this parameter. context - The
   * parameter name.
   */
  public const ERROR_TYPE_invalidTypeInSelect = 'invalidTypeInSelect';
  /**
   * The input container version contains a deprecated GA content experiment
   * macro that needs to be removed.
   */
  public const ERROR_TYPE_gaExperimentMacroIsDeprecated = 'gaExperimentMacroIsDeprecated';
  /**
   * Reminder: new error types will be treated as internal errors and trigger
   * alerts unless they are handled in
   * j/c/g/analytics/containertag/compiler/ErrorReporter.java&l=104 Please also
   * remember to add new error types to CTUI at
   * j/c/g/analytics/containertag/ui/app/components/container/compilererror/
   * HTML is not sanitized and contains unsafe content.
   */
  public const ERROR_TYPE_unsafeHtmlContent = 'unsafeHtmlContent';
  /**
   * HTML attribute is not sanitized and the value is unsafe.
   */
  public const ERROR_TYPE_unsafeHtmlAttributeValue = 'unsafeHtmlAttributeValue';
  /**
   * CSS is not sanitized and contains unsafe content.
   */
  public const ERROR_TYPE_unsafeCssContent = 'unsafeCssContent';
  /**
   * The specified parameter was not found in the referenced entity. parent - 1
   * EntityKey with tag_key populated. context - The missing parameter in the
   * form of ".".
   */
  public const ERROR_TYPE_parameterReferenceNotFound = 'parameterReferenceNotFound';
  /**
   * The custom template has invalid runtime code. parent - Entity key for the
   * custom template. context - The error message.
   */
  public const ERROR_TYPE_invalidCustomTemplateRuntimeCode = 'invalidCustomTemplateRuntimeCode';
  /**
   * The container version contains a Google tag tag and a destination tag that
   * use the same destination ID. parent - Entity key for the conflicting Google
   * tag. context - The Google tag tag name.
   */
  public const ERROR_TYPE_conflictingDestinationRouting = 'conflictingDestinationRouting';
  /**
   * The container version has routing destinations, but is missing required
   * activity instances. parent - Empty. context - Empty. proposed_change - The
   * proposed change to add the missing activities.
   */
  public const ERROR_TYPE_missingRequiredActivity = 'missingRequiredActivity';
  /**
   * The container version contains a product destination tag with a destination
   * ID value that cannot be resolved statically. parent - Entity key of the
   * destination tag. context - The name of the variable that could not be
   * resolved. If the destination ID was a template value, then this will be the
   * serialized value.
   */
  public const ERROR_TYPE_unresolvableDestinationTag = 'unresolvableDestinationTag';
  /**
   * The container version contains a product destination tag with a destination
   * ID value that is in an invalid format. parent - Entity key of the
   * destination tag. context - The invalid destination ID.
   */
  public const ERROR_TYPE_invalidDestinationTag = 'invalidDestinationTag';
  /**
   * GTM ToS is required for publishing versions with 3P tags. parent - not set
   * context - Empty.
   */
  public const ERROR_TYPE_tosRequiredForThirdPartyTags = 'tosRequiredForThirdPartyTags';
  /**
   * @var string
   */
  public $errorMessage;
  /**
   * @var string
   */
  public $errorType;

  /**
   * @param string $errorMessage
   */
  public function setErrorMessage($errorMessage)
  {
    $this->errorMessage = $errorMessage;
  }
  /**
   * @return string
   */
  public function getErrorMessage()
  {
    return $this->errorMessage;
  }
  /**
   * @param self::ERROR_TYPE_* $errorType
   */
  public function setErrorType($errorType)
  {
    $this->errorType = $errorType;
  }
  /**
   * @return self::ERROR_TYPE_*
   */
  public function getErrorType()
  {
    return $this->errorType;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(CompilerErrorLite::class, 'Google_Service_TagManager_CompilerErrorLite');
