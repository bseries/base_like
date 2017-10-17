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
use base_core\extensions\cms\Panes;

extract(Message::aliases());

Panes::register('user.likes', [
	'title' => $t('Likes', ['scope' => 'base_like']),
	'url' => ['controller' => 'likes', 'action' => 'index', 'library' => 'base_like', 'admin' => true],
	'weight' => 70
]);

?>