<?php

interface Greeter {

	public function greet($who);
}

class GermanGreeter implements Greeter {

	public function greet($who) {
		return "Hallo $who!";
	}
}

interface Sayer {

	public function say($what);
}

class DefaultSayer implements Sayer {

	public function say($what) {
		echo $what;
	}
}

class SpokenGreeter implements Greeter {

	/**
	 * @var Sayer
	 */
	private $sayer;

	/**
	 * @var Greeter
	 */
	private $actualGreeter;

	public function __construct(Sayer $sayer, Greeter $actualGreeter) {
		$this->sayer         = $sayer;
		$this->actualGreeter = $actualGreeter;
	}

	public function greet($who) {
		$this->sayer->say($this->actualGreeter->greet($who));
	}
}

$spokenGreeter = new SpokenGreeter(new DefaultSayer(), new GermanGreeter());
$spokenGreeter->greet("World");