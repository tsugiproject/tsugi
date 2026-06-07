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

namespace Google\Service\Aiplatform;

class GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentData extends \Google\Collection
{
  protected $collection_key = 'turns';
  protected $agentConfigType = GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig::class;
  protected $agentConfigDataType = '';
  protected $agentsType = GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig::class;
  protected $agentsDataType = 'map';
  protected $developerInstructionType = GoogleCloudAiplatformV1EvaluationInstanceInstanceData::class;
  protected $developerInstructionDataType = '';
  protected $eventsType = GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataEvents::class;
  protected $eventsDataType = '';
  protected $toolsType = GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataTools::class;
  protected $toolsDataType = '';
  /**
   * A JSON string containing a list of tools available to an agent with info
   * such as name, description, parameters and required parameters.
   *
   * @deprecated
   * @var string
   */
  public $toolsText;
  protected $turnsType = GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataConversationTurn::class;
  protected $turnsDataType = 'array';

  /**
   * Optional. Deprecated: Use `agent_eval_data` instead. Agent configuration.
   *
   * @param GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig $agentConfig
   */
  public function setAgentConfig(GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig $agentConfig)
  {
    $this->agentConfig = $agentConfig;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig
   */
  public function getAgentConfig()
  {
    return $this->agentConfig;
  }
  /**
   * Optional. The static Agent Configuration. This map defines the graph
   * structure of the agent system. Key: agent_id (matches the `author` field in
   * events). Value: The static configuration of the agent (tools, instructions,
   * sub-agents).
   *
   * @param GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig[] $agents
   */
  public function setAgents($agents)
  {
    $this->agents = $agents;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentConfig[]
   */
  public function getAgents()
  {
    return $this->agents;
  }
  /**
   * Optional. Deprecated: Use `agents.developer_instruction` or
   * `turns.events.active_instruction` instead. A field containing instructions
   * from the developer for the agent.
   *
   * @deprecated
   * @param GoogleCloudAiplatformV1EvaluationInstanceInstanceData $developerInstruction
   */
  public function setDeveloperInstruction(GoogleCloudAiplatformV1EvaluationInstanceInstanceData $developerInstruction)
  {
    $this->developerInstruction = $developerInstruction;
  }
  /**
   * @deprecated
   * @return GoogleCloudAiplatformV1EvaluationInstanceInstanceData
   */
  public function getDeveloperInstruction()
  {
    return $this->developerInstruction;
  }
  /**
   * A list of events.
   *
   * @param GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataEvents $events
   */
  public function setEvents(GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataEvents $events)
  {
    $this->events = $events;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataEvents
   */
  public function getEvents()
  {
    return $this->events;
  }
  /**
   * List of tools.
   *
   * @deprecated
   * @param GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataTools $tools
   */
  public function setTools(GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataTools $tools)
  {
    $this->tools = $tools;
  }
  /**
   * @deprecated
   * @return GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataTools
   */
  public function getTools()
  {
    return $this->tools;
  }
  /**
   * A JSON string containing a list of tools available to an agent with info
   * such as name, description, parameters and required parameters.
   *
   * @deprecated
   * @param string $toolsText
   */
  public function setToolsText($toolsText)
  {
    $this->toolsText = $toolsText;
  }
  /**
   * @deprecated
   * @return string
   */
  public function getToolsText()
  {
    return $this->toolsText;
  }
  /**
   * Optional. The chronological list of conversation turns. Each turn
   * represents a logical execution cycle (e.g., User Input -> Agent Response).
   *
   * @param GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataConversationTurn[] $turns
   */
  public function setTurns($turns)
  {
    $this->turns = $turns;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentDataConversationTurn[]
   */
  public function getTurns()
  {
    return $this->turns;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentData::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1EvaluationInstanceDeprecatedAgentData');
