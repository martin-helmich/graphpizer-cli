<?php
namespace Helmich\Graphizer\Persistence\Op;


trait PropertyFilter {

	protected function filterProperties(array $properties) {
		$properties = array_filter($properties, function ($value) {
			return $value !== NULL;
		});
		return $properties;
	}
} 