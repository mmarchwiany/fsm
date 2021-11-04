<?php

namespace mmarchwiany\StateMachine;

class Transition
{
    public function __construct(
        public string $from,
        public string $action,
        public string $to,
        public $callback = null)
    {

    }
}