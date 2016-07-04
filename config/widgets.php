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

namespace base_like\config;

use lithium\g11n\Message;
use base_core\extensions\cms\Widgets;
use base_like\models\Likes;

extract(Message::aliases());

Widgets::register('liked',  function() use ($t) {
	$things = Likes::find('count', [
		'conditions' => [],
		'group' => ['model', 'foreign_key']
	]);
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
	$results = Likes::find('allGrouped', [
		'order' => ['SUM(count_real)' => 'DESC'],
		'limit' => 10
	]);
	$data = [];
	foreach ($results as $result) {
		if (!$poly = $result->poly()) {
			continue;
		}
		$data[$poly->title()] = $result->count('real');
	}
	return [
		'title' => $t('Top 10 of most liked Things'),
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