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
	return [
		'title' => $t('Liked', ['scope' => 'base_like']),
		'data' => [
			$t('Things', ['scope' => 'base_like']) => Likes::find('count'),
			$t('Likes', ['scope' => 'base_like']) => Likes::find('all')->reduce(function($carry, $item) {
				return $carry + $item->count('real');
			}, 0)
		]
	];
}, [
	'type' => Widgets::TYPE_COUNTER,
	'group' => Widgets::GROUP_DASHBOARD
]);

?>