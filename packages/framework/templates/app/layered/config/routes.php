<?php

declare(strict_types=1);

use App\{{ namespace }}\Presentation\Http\Controller\Health\Show\Controller as HealthShowController;
use App\{{ namespace }}\Presentation\Http\Controller\Contact\Submit\Controller as ContactSubmitController;

return [
    ['GET', '{{ healthPath }}', HealthShowController::class],
    ['POST', '{{ contactPath }}', ContactSubmitController::class],
];
