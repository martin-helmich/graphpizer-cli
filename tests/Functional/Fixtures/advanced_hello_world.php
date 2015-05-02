<?php

interface Greeter {

	public function greet($who);
}

class GermanGreeter implements Greeter {

	public function greet($who) {
		// German for "Hello $who"
		return "Hallo $who!";
	}
}

interface Sayer {

	public function say($what);
}

trait PrintTrait {
	public function write($str) {
		echo $str;
	}
}

class DefaultSayer implements Sayer {

	use PrintTrait;

	public function say($what) {
		$this->write($what);
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

class Application {
	public function run() {
		$spokenGreeter = new SpokenGreeter(new DefaultSayer(), new GermanGreeter());
		$spokenGreeter->greet("World");
	}
}

$app = new Application();
$app->run();