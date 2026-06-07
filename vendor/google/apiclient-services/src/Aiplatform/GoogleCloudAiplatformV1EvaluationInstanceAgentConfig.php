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

class GoogleCloudAiplatformV1EvaluationInstanceAgentConfig extends \Google\Collection
{
  protected $collection_key = 'subAgents';
  /**
   * Optional. Unique identifier of the agent. This ID is used to refer to this
   * agent, e.g., in AgentEvent.author, or in the `sub_agents` field. It must be
   * unique within the `agents` map.
   *
   * @var string
   */
  public $agentId;
  /**
   * Optional. The type or class of the agent (e.g., "LlmAgent", "RouterAgent",
   * "ToolUseAgent"). Useful for the autorater to understand the expected
   * behavior of the agent.
   *
   * @var string
   */
  public $agentType;
  /**
   * Optional. A high-level description of the agent's role and
   * responsibilities. Critical for evaluating if the agent is routing tasks
   * correctly.
   *
   * @var string
   */
  public $description;
  protected $developerInstructionType = GoogleCloudAiplatformV1EvaluationInstanceInstanceData::class;
  protected $developerInstructionDataType = '';
  /**
   * Optional. The list of valid agent IDs (names) that this agent can delegate
   * to. This defines the directed edges in the agent system graph topology.
   *
   * @var string[]
   */
  public $subAgents;
  protected $toolsType = GoogleCloudAiplatformV1EvaluationInstanceAgentConfigTools::class;
  protected $toolsDataType = '';
  /**
   * A JSON string containing a list of tools available to an agent with info
   * such as name, description, parameters and required parameters.
   *
   * @var string
   */
  public $toolsText;

  /**
   * Optional. Unique identifier of the agent. This ID is used to refer to this
   * agent, e.g., in AgentEvent.author, or in the `sub_agents` field. It must be
   * unique within the `agents` map.
   *
   * @param string $agentId
   */
  public function setAgentId($agentId)
  {
    $this->agentId = $agentId;
  }
  /**
   * @return string
   */
  public function getAgentId()
  {
    return $this->agentId;
  }
  /**
   * Optional. The type or class of the agent (e.g., "LlmAgent", "RouterAgent",
   * "ToolUseAgent"). Useful for the autorater to understand the expected
   * behavior of the agent.
   *
   * @param string $agentType
   */
  public function setAgentType($agentType)
  {
    $this->agentType = $agentType;
  }
  /**
   * @return string
   */
  public function getAgentType()
  {
    return $this->agentType;
  }
  /**
   * Optional. A high-level description of the agent's role and
   * responsibilities. Critical for evaluating if the agent is routing tasks
   * correctly.
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
   * Optional. Contains instructions from the developer for the agent. Can be
   * static or a dynamic prompt template used with the `AgentEvent.state_delta`
   * field.
   *
   * @param GoogleCloudAiplatformV1EvaluationInstanceInstanceData $developerInstruction
   */
  public function setDeveloperInstruction(GoogleCloudAiplatformV1EvaluationInstanceInstanceData $developerInstruction)
  {
    $this->developerInstruction = $developerInstruction;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluationInstanceInstanceData
   */
  public function getDeveloperInstruction()
  {
    return $this->developerInstruction;
  }
  /**
   * Optional. The list of valid agent IDs (names) that this agent can delegate
   * to. This defines the directed edges in the agent system graph topology.
   *
   * @param string[] $subAgents
   */
  public function setSubAgents($subAgents)
  {
    $this->subAgents = $subAgents;
  }
  /**
   * @return string[]
   */
  public function getSubAgents()
  {
    return $this->subAgents;
  }
  /**
   * List of tools.
   *
   * @param GoogleCloudAiplatformV1EvaluationInstanceAgentConfigTools $tools
   */
  public function setTools(GoogleCloudAiplatformV1EvaluationInstanceAgentConfigTools $tools)
  {
    $this->tools = $tools;
  }
  /**
   * @return GoogleCloudAiplatformV1EvaluationInstanceAgentConfigTools
   */
  public function getTools()
  {
    return $this->tools;
  }
  /**
   * A JSON string containing a list of tools available to an agent with info
   * such as name, description, parameters and required parameters.
   *
   * @param string $toolsText
   */
  public function setToolsText($toolsText)
  {
    $this->toolsText = $toolsText;
  }
  /**
   * @return string
   */
  public function getToolsText()
  {
    return $this->toolsText;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(GoogleCloudAiplatformV1EvaluationInstanceAgentConfig::class, 'Google_Service_Aiplatform_GoogleCloudAiplatformV1EvaluationInstanceAgentConfig');
