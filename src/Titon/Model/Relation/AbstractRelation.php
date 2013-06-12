<?php

namespace Titon\Model\Relation;

use Titon\Common\Base;
use Titon\Model\Relation;

abstract class AbstractRelation extends Base implements Relation {

	protected $_config = [
		'alias' => '',
		'model' => '',
		'foreignKey' => '',
		'dependent' => true
	];

	public function __construct($alias, $model, array $config = []) {
		parent::__construct($config);

		$this->setAlias($alias);
		$this->setModel($model);
	}

	public function getAlias() {
		return $this->config->alias;
	}

	public function getForeignKey() {
		return $this->config->foreignKey;
	}

	public function getModel() {
		return $this->config->model;
	}

	public function isDependent() {
		return $this->config->dependent;
	}

	public function setAlias($alias) {
		$this->config->alias = $alias;

		return $this;
	}

	public function setDependent($state) {
		$this->config->dependent = $state;

		return $this;
	}

	public function setForeignKey($key) {
		$this->config->foreignKey = $key;

		return $this;
	}

	public function setModel($model) {
		$this->config->model = $model;

		return $this;
	}

}