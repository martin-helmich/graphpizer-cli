<?php
/*
 * GraPHPizer source code analytics engine (cli component)
 * Copyright (C) 2015  Martin Helmich <kontakt@martin-helmich.de>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Helmich\Graphizer\Utility;

/**
 * Helper trait for observable objects.
 *
 * @package    Helmich\Graphizer
 * @subpackage Utility
 */
trait ObservableTrait {

	/**
	 * @var callable[]
	 */
	private $listeners = [];

	/**
	 * Adds a new listener for an event type
	 *
	 * @param string   $event    The event name
	 * @param callable $listener The listener function
	 * @return void
	 */
	protected function addListener($event, callable $listener) {
		if (!isset($this->listeners[$event])) {
			$this->listeners[$event] = [];
		}
		$this->listeners[$event][] = $listener;
	}

	/**
	 * Notifies listeners about an event.
	 *
	 * @param string $event   The event name
	 * @param array  ...$args Event data
	 * @return void
	 */
	protected function notify($event, ...$args) {
		if (isset($this->listeners[$event])) {
			foreach ($this->listeners[$event] as $listener) {
				call_user_func_array($listener, $args);
			}
		}
	}
}