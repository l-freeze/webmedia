<?php

declare(strict_types=1);

use App\{{ namespace }}\Presentation\Http\Controller\Index\Controller as IndexController;

return [
    ['GET', '{{ basePath }}', IndexController::class],
];
