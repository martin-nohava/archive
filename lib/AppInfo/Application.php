<?php
declare(strict_types=1);
// SPDX-FileCopyrightText: Martin Nohava <martin.nohava@vut.cz>
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace OCA\Archive\AppInfo;

use OCP\AppFramework\App;

class Application extends App {
	public const APP_ID = 'archive';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}
}
