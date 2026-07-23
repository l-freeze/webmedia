<?php

declare(strict_types=1);

use App\Minimal\Presentation\Http\Controller\Index\Controller as IndexController;

return [
    ['GET', '/', IndexController::class],
];
