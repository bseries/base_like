<?php
/**
 * Base Like
 *
 * Copyright (c) 2015 Atelier Disko - All rights reserved.
 *
 * Licensed under the AD General Software License v1.
 *
 * This software is proprietary and confidential. Redistribution
 * not permitted. Unless required by applicable law or agreed to
 * in writing, software distributed on an "AS IS" BASIS, WITHOUT-
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *
 * You should have received a copy of the AD General Software
 * License. If not, see http://atelierdisko.de/licenses.
 */

namespace base_like\models;

// Anonymous likes on arbitrary entities (via model/foreignKey).
class Likes extends \base_core\models\Base {

	protected $_actsAs = [
		'base_core\extensions\data\behavior\Searchable' => [
			'fields' => [
				'model',
				'foreign_key',
				'count'
			]
		]
	];

	// Adds a single like to given entity.
	//
	// Does an "UPSERT", operates as atomic as possible. It is currently not
	// possible to do this through lithium so we revert to raw SQL here.
	// http://dev.mysql.com/doc/refman/5.1/en/insert-on-duplicate.html
	public static function add($model, $foreignKey) {
		$sql  = 'INSERT INTO `likes` (`id`, `model`, `foreign_key`, `count`, `is_published`) ';
		$sql .= 'VALUES (NULL, :model, :foreignKey, 1, 0) ';
		$sql .= 'ON DUPLICATE KEY UPDATE `count` = `count` + 1';

		$stmnt  = static::pdo()->prepare($sql);
		$result = $stmnt->execute([
			'model' => $model,
			'foreignKey' => $foreignKey
		]);
var_dump($result);
		return $result;
	}

	// Returns the entitey that's been liked.
	public function liked($entity) {
		$model = $entity->model;

		return $model::find('first', [
			'conditions' => [
				'id' => $entity->foreign_key
			]
		]);
	}
}

?>