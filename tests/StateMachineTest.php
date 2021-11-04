<?php

use mmarchwiany\StateMachine\StatefulInterface;
use mmarchwiany\StateMachine\StateMachine;
use mmarchwiany\StateMachine\Transition;
use PHPUnit\Framework\TestCase;

class StateMachineTest extends TestCase
{
    private StateMachine $stateMachine;

    public function setUp(): void
    {
        parent::setUp();

        $this->stateMachine = new StateMachine(new StatefulTransaction(), [
            new Transition('new', 'process', 'pending'),
            new Transition('pending', 'confirm', 'confirmed'),
            new Transition('pending', 'cancel', 'cancelled'),
            new Transition('confirmed', 'cancel', 'cancelled'),
            new Transition('cancelled', 'reprocess', 'pending'),
        ]);
    }

    /** @test  */
    public function return_all_allowed_states(){
        $this->assertCount(1, $this->stateMachine->getAllowedActions());
        $this->assertEquals(['process'], $this->stateMachine->getAllowedActions());
    }

    /** @test  */
    public function is_action_valid(){
        $this->assertTrue($this->stateMachine->isActionValid('process'));
        $this->assertFalse($this->stateMachine->isActionValid('confirm'));
    }

    /** @test */
    public function disallow_register_duplacate_action(){
        $this->expectExceptionMessage("Transition for action process already exists.");
        new StateMachine(new StatefulTransaction(), [
            new Transition('new', 'process', 'pending'),
            new Transition('new', 'process', 'confirmed'),
        ]);
    }

    /** @test */
    public function disallow_call_invalid_action(){
        $this->expectExceptionMessage("Action cancel not allowed.");
        $this->stateMachine->process('cancel');
    }

    /** @test  */
    public function process_actions(){
        $this->stateMachine->process('process');
        $this->assertEquals('pending', $this->stateMachine->getCurrentState());
    }

    /** @test */
    public function process_multiple_actions(){
        $this->stateMachine->process('process')
            ->process('confirm')
            ->process('cancel')
            ->process('reprocess');
        $this->assertEquals('pending', $this->stateMachine->getCurrentState());
    }

    /** @test */
    public function triggers_callbacks_with_params(){
        $monitor = 'off';
        $timestamp = time();

        $stateMachine = new StateMachine(new StatefulTransaction('off'), [
            new Transition('off', 'turn_on', 'on', function($args) use (&$monitor, &$timestamp){
                $monitor='on';
                $timestamp = $args[0];
            }),
            new Transition('on', 'turn_off', 'off', function($args) use (&$monitor, &$timestamp){
                $monitor='off';
                $timestamp = $args[0];
            }),
        ]);

        $time = time();
        $stateMachine->process('turn_on', [$time]);
        $this->assertEquals('on', $stateMachine->getCurrentState());
        $this->assertEquals('on', $monitor);
        $this->assertEquals($time, $timestamp);

        $time = time();
        $stateMachine->process('turn_off', [$time]);
        $this->assertEquals('off', $stateMachine->getCurrentState());
        $this->assertEquals('off', $monitor);
        $this->assertEquals($time, $timestamp);
    }
}

class StatefulTransaction implements StatefulInterface{

    public function __construct(private string $state = 'new'){
    }

    public function getCurrentState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }
}