<?php

/*
 * This file is part of steel97/flarum-mailopost.
 *
 * Copyright (c) 2024 Ivan Yv.
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Steel97\FlarumMailopost;

use Flarum\Extend;
use FoF\Upload\Extenders\LoadFilesRelationship;

return [
    
    (new Extend\Mail())->driver(MailopostDriver::class)
        
        
    
];
