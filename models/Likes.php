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
use lithium\core\Libraries;
use lithium\data\Entity;

// Class to enable non-safe anonymous likes on any entity
// identifieable via model / foreign key.
//
// * Non-safe. Whoever really wants to game the count may do it. It is
//   assumed that like count will not be used in critical places.
//   This assumption simplifies backend logic.
//
// * Because we don't need to auth, we can have anonymous likers.
//   (user_id is optional)
//
// * URLs are universal, model/foreign key combinations are not.
//   BUT we cannot easily map URLs back to their model for
//   further calulations (i.e. top liked products).
//
// Allows to pre-seed likes with an arbitrary amount.
//
class Likes extends \base_core\models\Base {

	protected $_actsAs = [
		'base_core\extensions\data\behavior\Searchable' => [
			'fields' => [
				'model',
				'foreign_key',
				'count_real',
				'User.name',
				'User.number'
			]
		]
	];

	public $belongsTo = [
		'User' => [
			'to' => 'base_core\models\Users',
			'key' => 'user_id'
		]
	];

	public static function init() {
		static::finder('grouped', function($self, $params, $chain) {
			if (is_array($params['options']['conditions']['foreign_key'])) {
				$type = 'all';
			} else {
				$type = 'first';
			}
			return static::find($type, [
				'conditions' => $params['options']['conditions'],
				'fields' => [
					'model',
					'foreign_key',
					'SUM(count_real) AS count_real',
					'SUM(count_seed) AS count_seed'
				],
				'group' => ['model', 'foreign_key']
			]);
		});
	}

	// Adds a single real like to given entity, identified by model/foreign key
	// combination.
	public static function add($model, $foreignKey, $userId, $sessionKey) {
		if (!$userId && !$sessionKey) {
			throw new InvalidArgumentException('No user id or session key given.');
		}
		$conditions = [
			'model' => $model,
			'foreign_key' => $foreignKey,
			'user_id' => $userId,
			'session_key' => $sessionKey
		];
		if (static::find('count', compact('conditions'))) {
			return false;
		}
		return static::create($conditions + [
			'count_real' => 1
		])->save();
	}

	public static function get($model, $foreignKey, $userId, $sessionKey) {
		if (!$userId && !$sessionKey) {
			throw new InvalidArgumentException('No user id or session key given.');
		}
		$conditions = [
			'model' => $model,
			'foreign_key' => $foreignKey,
			'user_id' => $userId,
			'session_key' => $sessionKey
		];
		return static::find('first', compact('conditions'));
	}

	// Returns `true` if seed happened, `null` if there is nothing to seed, `false`
	// if seeding failed.
	public static function seed($model, $foreignKey) {
		$isInitialized = static::find('count', [
			'conditions' => [
				'model' => $model,
				'foreign_key' => $foreignKey
			]
		]);
		if ($isInitialized) {
			return null;
		}
		$item = static::create([
			'user_id' => null,
			'session_key' => null,
			'model' => $model,
			'foreign_key' => $foreignKey,
			'count_real' => 0,
			'count_seed' => static::_seedCount()
		]);
		return $item->save();
	}

	protected static function _seedCount() {
		if (($seed = Settings::read('like.seed')) === false) {
			return 0;
		}
		if (is_array($seed) && count($seed) === 2) {
			return mt_rand($seed[0], $seed[1]);
		} elseif (is_int($seed)) {
			return $seed;
		}
		throw new Exception('Invalid seed.');
	}

	public function count($entity, $type) {
		switch ($type) {
			case 'real':
				return $entity->count_real;
			case 'fake':
				return $entity->count_seed;
			case 'virtual':
				return $entity->count_real + $entity->count_seed;
			break;
			default:
				throw new InvalidArgumentException("Invalid count type `{$type}` given.");
		}
	}

	public function hasGiven($entity, $userId, $sessionKey) {
		if (!$userId && !$sessionKey) {
			throw new InvalidArgumentException('No user id or session key given.');
		}
		$conditions = [
			'model' => $entity->model,
			'foreign_key' => $entity->foreign_key
		];
		if ($userId) {
			$conditions['user_id'] = $userId;
		} else {
			$conditions['session_key'] = $sessionKey;
		}
		return (boolean) static::find('count', compact('conditions'));
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

Likes::init();

// Normalizes model parameter to fully namespaced one.
// idempotent
$normalizeModel = function($model) {
	if (strpos($model, '\\') !== false) {
		return $model;
	}
	// Convert product-groups into ProductGroups.
	$model = ucfirst(Inflector::camelize($model));

	// Will have no leading backslash.
	$located = Libraries::locate('models', $model);

	return $located ?: $model;
};
Likes::applyFilter('save', function($self, $params, $chain) use ($normalizeModel) {
	$entity = $params['entity'];
	$data =& $params['data'];

	if (isset($data['model'])) {
		$data['model'] = $normalizeModel($data['model']);
	}
	if ($entity->model) {
		$entity->model = $normalizeModel($entity->model);
	}

	// Lazily update all session ids to augment user_id.
	if ($entity->session_key && $entity->user_id) {
		Likes::update(
			['session_key' => $entity->session_key, 'user_id' => $entity->user_id],
			['session_key' => $entity->session_key]
		);
	}
	return $chain->next($self, $params, $chain);
});
Likes::applyFilter('find', function($self, $params, $chain) use ($normalizeModel) {
	$conditions =& $params['options']['conditions'];

	if (isset($conditions['model'])) {
		$conditions['model'] = $normalizeModel($conditions['model']);
	}
	if (!empty($conditions['user_id'])) {
		unset($conditions['session_key']);
	} elseif (!empty($conditions['session_key'])) {
		unset($conditions['user_id']);
	}
	return $chain->next($self, $params, $chain);
});

?>