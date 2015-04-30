<?php
namespace Helmich\Graphizer\Utility;

trait ObservableTrait {

	/**
	 * @var callable[]
	 */
	private $listeners = [];

	protected function addListener($event, callable $listener) {
		if (!isset($this->listeners[$event])) {
			$this->listeners[$event] = [];
		}
		$this->listeners[$event][] = $listener;
	}

	protected function notify($event, ...$args) {
		if (isset($this->listeners[$event])) {
			foreach ($this->listeners[$event] as $listener) {
				call_user_func_array($listener, $args);
			}
		}
	}
}