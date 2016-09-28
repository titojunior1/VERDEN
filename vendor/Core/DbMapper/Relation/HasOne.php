<?php

/**
 * Core_DbMapper_Relation_HasMany
 *
 * Classe para o tipo de relacionamento 'has one'
 *
 * @package Core\DbMapper
 */
class Core_DbMapper_Relation_HasOne extends Core_DbMapper_Relation_RelationAbstract {

	/**
	 * Load query object with current relation data
	 *
	 * @return Core_DbMapper_Query
	 */
	public $entity = null;

	protected function toQuery() {
		return $this->mapper()->all($this->entityName(), $this->conditions())->order($this->relationOrder())->limit(1);
	}

	public function entity() {
		if (! $this->entity) {
			$this->entity = $this->execute();
			if ($this->entity instanceof Core_DbMapper_Query) {
				$this->entity = $this->entity->first();
			}
		}
		return $this->entity;
	}

	/**
	 * isset() functionality passthrough to entity
	 */
	public function __isset($key) {
		$entity = $this->execute();
		if ($entity) {
			return isset($entity->$key);
		} else {
			return false;
		}
	}

	/**
	 * Getter passthrough to entity
	 */
	public function __get($var) {
		if ($this->entity()) {
			return $this->entity()->$var;
		} else {
			return null;
		}
	}

	/**
	 * Setter passthrough to entity
	 */
	public function __set($var, $value) {
		if ($this->entity()) {
			$this->entity()->$var = $value;
		}
	}
}
