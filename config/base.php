<?php
/**
 * Copyright 2015 David Persson. All rights reserved.
 * Copyright 2016 Atelier Disko. All rights reserved.
 *
 * Use of this source code is governed by a BSD-style
 * license that can be found in the LICENSE file.
 */

namespace base_like\config;

use base_core\extensions\cms\Settings;

// Allows to seed newly created likes. Can either
// be a fixed integer or an array with two integers
// which will be used as the random range. By default
// disabled using `false`.
Settings::register('likes.seed', false);

?>