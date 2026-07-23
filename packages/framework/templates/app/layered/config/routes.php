<?php

declare(strict_types=1);

use App\{{ namespace }}\Presentation\Http\Controller\Health\Show\Controller as HealthShowController;
use App\{{ namespace }}\Presentation\Http\Controller\Contact\Show\Controller as ContactShowController;
use App\{{ namespace }}\Presentation\Http\Controller\Contact\Submit\Controller as ContactSubmitController;

return [
    ['GET', '{{ healthPath }}', HealthShowController::class],
    ['GET', '{{ contactPath }}', ContactShowController::class],
    ['POST', '{{ contactPath }}', ContactSubmitController::class],
];
