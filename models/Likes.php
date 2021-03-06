<?php
/**
 * Copyright 2015 David Persson. All rights reserved.
 * Copyright 2016 Atelier Disko. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace base_like\models;

use Exception;
use InvalidArgumentException;
use base_core\extensions\cms\Settings;
use lithium\core\Libraries;

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
		'base_core\extensions\data\behavior\Polymorphic',
		'base_core\extensions\data\behavior\RelationsPlus',
		'base_core\extensions\data\behavior\Timestamp',
		'base_core\extensions\data\behavior\Searchable' => [
			'fields' => [
				'User.name',
				'User.number',
				'created',
				'model',
				'count_real'
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
		static::finder('firstGrouped', function($self, $params, $chain) {
			return static::find('first', [
				'conditions' => $params['options']['conditions'],
				'fields' => [
					'model',
					'foreign_key',
					'SUM(count_real) AS count_real',
					'SUM(count_seed) AS count_seed'
				],
				'group' => ['model', 'foreign_key']
			] + $params['options']);
		});
		static::finder('allGrouped', function($self, $params, $chain) {
			return static::find('all', [
				'conditions' => $params['options']['conditions'],
				'fields' => [
					'model',
					'foreign_key',
					'SUM(count_real) AS count_real',
					'SUM(count_seed) AS count_seed'
				],
				'group' => ['model', 'foreign_key']
			] + $params['options']);
		});
	}

	// Adds a single real like to given entity, identified by model/foreign key
	// combination.
	public static function add($model, $foreignKey, $userId, $sessionKey, array $options = []) {
		if (!$userId && !$sessionKey) {
			throw new InvalidArgumentException('No user id or session key given.');
		}
		$options += ['seed' => false];

		if ($options['seed']) {
			if (static::seed($model, $foreignKey) === false) {
				return false;
			}
		}
		$conditions = [
			'model' => $model,
			'foreign_key' => $foreignKey,
			'user_id' => $userId,
			'session_key' => $sessionKey
		];
		if (static::find('count', compact('conditions'))) {
			// User already liked the entity, not an error
			// condition per se, but we should not get here.
			return null;
		}
		$item = static::create($conditions + [
			'count_real' => 1
		]);
		if ($item->save()) {
			return $item;
		}
		return false;
	}

	public static function get($model, $foreignKey, $userId, $sessionKey, array $options = []) {
		if (!$userId && !$sessionKey) {
			throw new InvalidArgumentException('No user id or session key given.');
		}
		$options += ['seed' => false];

		if ($options['seed']) {
			if (static::seed($model, $foreignKey) === false) {
				return false;
			}
		}
		$conditions = [
			'model' => $model,
			'foreign_key' => $foreignKey
		];
		$result = static::find('firstGrouped', compact('conditions'));

		if (!$result) {
			return [
				'model' => $model,
				'foreign_key' => $foreignKey,
				'count' => 0,
				'given' => false
			];
		}
		return [
			'model' => $model,
			'foreign_key' => $foreignKey,
			'count' => $result->count('virtual'),
			'given' => $result->hasGiven(
				$userId,
				$sessionKey
			)
		];
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
}

Likes::init();

Likes::applyFilter('save', function($self, $params, $chain) {
	$entity = $params['entity'];

	// Lazily update all session ids to augment user_id. In both directions.
	if ($entity->session_key && $entity->user_id) {
		Likes::update(
			['user_id' => $entity->user_id],
			['session_key' => $entity->session_key]
		);
		Likes::update(
			['session_key' => $entity->session_key],
			['user_id' => $entity->user_id]
		);
	}
	return $chain->next($self, $params, $chain);
});
Likes::applyFilter('find', function($self, $params, $chain) {
	$conditions =& $params['options']['conditions'];

	if (!empty($conditions['user_id'])) {
		unset($conditions['session_key']);
	} elseif (!empty($conditions['session_key'])) {
		unset($conditions['user_id']);
	}
	return $chain->next($self, $params, $chain);
});

?>