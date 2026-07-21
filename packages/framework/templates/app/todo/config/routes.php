<?php

declare(strict_types=1);

use App\{{ namespace }}\Presentation\Http\Controller\Todo\Complete\Controller as TodoCompleteController;
use App\{{ namespace }}\Presentation\Http\Controller\Todo\Index\Controller as TodoIndexController;
use App\{{ namespace }}\Presentation\Http\Controller\Todo\Store\Controller as TodoStoreController;

return [
    ['GET', '{{ basePath }}', TodoIndexController::class],
    ['POST', '{{ basePath }}', TodoStoreController::class],
    ['POST', '{{ todoCompletePath }}', TodoCompleteController::class],
];
