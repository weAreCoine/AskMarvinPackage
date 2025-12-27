<?php

arch('it will not use debugging functions')
    ->expect(['dd', 'dump', 'xdebug_break'])
    ->each->not->toBeUsed()
    ->ignoring(['tests', 'Marvin\Ask\Handlers\ExceptionsHandler']);
