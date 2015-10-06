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

use Exception;
use InvalidArgumentException;
use base_core\extensions\cms\Settings;
use lithium\util\Inflector;

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
				'foreign_key',
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
		$model = Inflector::camelize($model); // convert product-groups into ProductGroups

		$sql  = 'INSERT INTO `likes` (`id`, `model`, `foreign_key`, `count_real`, `count_seed`) ';
		$sql .= 'VALUES (NULL, :model, :foreignKey, 0, :countSeed) ';
		$sql .= 'ON DUPLICATE KEY UPDATE `count_real` = `count_real` + 1';

		$stmnt  = static::pdo()->prepare($sql);
		$result = $stmnt->execute([
			'model' => $model,
			'foreignKey' => $foreignKey,
			'countSeed' => static::_seed()
		]);
		return $result;
	}

	protected static function _seed() {
		if (($seed = Settings::read('like.seed') === false)) {
			return 0;
		}
		if (is_array($seed) && count($seed) === 2) {
			return mt_rand($seed[0], $seed[1]);
		} elseif (is_int($seed)) {
			return $seed;
		}
		throw new Exception('Invalid seed.');
	}

	// Will auto initialize a record, when it didn't exist. Returns the like count.
	public static function get($model, $foreignKey) {
		$item = Likes::find('first', [
			'conditions' => [
				'model' => $model,
				'foreign_key' => $foreignKey
			]
		]);

		if (!$item) {
			$item = Likes::create([
				'model' => $model,
				'foreign_key' => $foreignKey,
				'count_real' => 0,
				'count_seed' => static::_seed()
			]);
			if (!$item->save()) {
				return false;
			}
		}
		return static::create([
			'id' => $item->id,
			'count' => $item->count(),
			// Do not expose fake data mechanics.
			// 'count_real' => $item->count_real,
			// 'count_seed' => $item->count_seed
		], ['exists' => true]);
	}

	public function count($entity) {
		return $entity->count_real + $entity->count_seed;
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