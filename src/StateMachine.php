<?php

namespace mmarchwiany\StateMachine;

use InvalidArgumentException;

class StateMachine
{
    private array $transitions = [];

    public function __construct(
        private StatefulInterface $state,
        array $transitions,
    )
    {
        array_walk($transitions, fn($transition) => $this->registerTransition($transition));
    }

    public function getAllowedActions(): array{
        return array_keys($this->transitions[$this->state->getCurrentState()]);
    }

    public function isActionValid(string $action): bool{
        return in_array($action, $this->getAllowedActions());
    }

    public function process(string $action, array $params = []): self{
        if(!$this->isActionValid($action)){
            throw new InvalidArgumentException("Action {$action} not allowed.");
        }

        $transition = $this->transitions[$this->state->getCurrentState()][$action];

        if(is_callable($transition->callback)){
            call_user_func($transition->callback, $params);
        }

        $this->state->setState($transition->to);

        return $this;
    }

    public function getCurrentState(): string{
        return $this->state->getCurrentState();
    }

    private function registerTransition(Transition $transition){
        if(isset($this->transitions[$transition->from][$transition->action])){
            throw new InvalidArgumentException("Transition for action {$transition->action} already exists.");
        }

        if(!isset($this->transitions[$transition->from])){
            $this->transitions[$transition->from] = [];
        }

        $this->transitions[$transition->from][$transition->action] = $transition;
    }
}