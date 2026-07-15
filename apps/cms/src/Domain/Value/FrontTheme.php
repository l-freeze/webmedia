<?php

declare(strict_types=1);

namespace App\Cms\Domain\Value;

enum FrontTheme: string
{
    case CafeClean = 'cafe-clean';
    case TechEdge = 'tech-edge';
    case ExecutiveTrust = 'executive-trust';
    case DevDocs = 'dev-docs';
    case RecipeFresh = 'recipe-fresh';
    case PopGirly = 'pop-girly';
    case MamaSoft = 'mama-soft';
}
