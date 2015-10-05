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

use InvalidArgumentException;

// Class to enable non-safe anonymous likes on any entity
// identifieable via model / foreign key.
//
// * Non-safe. Whoever really wants to game the count may do it. It is
//   assumed that like count will not be used in critical places.
//   This assumption simplifies backend logic. Else we would
//   need to auth users and keep track of who liked what.
//
// * Because we don't need to auth, we can have anonymous likers.
//
// * URLs are universal, model/foreign key combinations are not.
//   BUT we cannot easily map URLs back to their model for
//   further calulations (i.e. top liked products).
//
class Likes extends \base_core\models\Base {

	protected $_actsAs = [
		'base_core\extensions\data\behavior\Searchable' => [
			'fields' => [
				'model',
				'count'
			]
		]
	];

	// Adds a single like to given entity, identified by model/foreign key
	// combination.
	//
	// Does an "UPSERT", operates as atomic as possible. It is currently not
	// possible to do this through lithium so we revert to raw SQL here.
	// http://dev.mysql.com/doc/refman/5.1/en/insert-on-duplicate.html
	public static function add($model, $foreignKey) {
		if (!$model || !$foreignKey) {
			throw new InvalidArgumentException('No model or foreign key given.');
		}
		$sql  = 'INSERT INTO `likes` (`id`, `model`, `foreignKey`, `count`, `is_published`) ';
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

	// Retrieve a polymorphic relationship.
	public function poly($entity) {
		$model = $entity->model;

		return $model::find('first', [
			'conditions' => [
				'id' => $entity->foreign_key,
			]
		]);
	}
}

?>