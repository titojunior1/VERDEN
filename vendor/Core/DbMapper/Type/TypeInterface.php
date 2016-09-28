<?php

interface Core_DbMapper_Type_TypeInterface {

	public function cast($value);

	public function get(Core_DbMapper_Entity $entity, $name);

	public function set(Core_DbMapper_Entity $entity, $name);

	public function _dump($value);

	public function dump($value);

	public function _load($value);

	public function load($value);

	public function adapterOptions();
}

