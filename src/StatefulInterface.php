<?php

namespace mmarchwiany\StateMachine;

interface StatefulInterface
{
    public function getCurrentState() : string;

    public function setState(string $state) : void;
}