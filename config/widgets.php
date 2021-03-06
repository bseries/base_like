<?php
/**
 * Copyright 2015 David Persson. All rights reserved.
 * Copyright 2016 Atelier Disko. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace base_like\config;

use lithium\g11n\Message;
use base_core\extensions\cms\Widgets;
use base_like\models\Likes;

extract(Message::aliases());

Widgets::register('liked',  function() use ($t) {
	$things = Likes::find('first', [
		'fields' => ['COUNT(DISTINCT(CONCAT(model, foreign_key)))']
	]);
	$things = $things ? current($things->data()) : 0;

	$likes = Likes::find('first', [
		'fields' => [
			'SUM(count_real) AS count_real'
		]
	])->count_real;

	return [
		'title' => $t('Liked', ['scope' => 'base_like']),
		'data' => [
			$t('Things', ['scope' => 'base_like']) => $things,
			$t('Likes', ['scope' => 'base_like']) => $likes
		]
	];
}, [
	'type' => Widgets::TYPE_COUNTER,
	'group' => Widgets::GROUP_DASHBOARD
]);

Widgets::register('likedTopTen', function() use ($t) {
	$results = Likes::find('all', [
		'limit' => 10,
		'conditions' => [
			'YEAR(created)' => (object) 'YEAR(NOW())',
			'MONTH(created)' => (object) 'MONTH(NOW()) - 1',
		],
		'group' => ['model', 'foreign_key'],
		'fields' => [
			'model', 'foreign_key', 'SUM(count_real) AS total_count_real'
		],
		'order' => [
			'total_count_real' => 'DESC'
		],
	]);
	$data = [];
	foreach ($results as $result) {
		if (!$poly = $result->poly()) {
			continue;
		}
		$data[$poly->title()] = $result->total_count_real;
	}
	return [
		'title' => $t('This month most liked Things'),
		'data' => $data,
		'url' => [
			'library' => 'base_like',
			'controller' => 'Likes',
			'action' => 'index'
		]
	];
}, [
	'type' => Widgets::TYPE_TABLE,
	'group' => Widgets::GROUP_DASHBOARD
]);


?>