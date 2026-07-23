<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Audit;

final class AuditTrail
{
    public const ATTRIBUTE = 'cms.audit_trail';

    private ?AuditSummary $summary = null;

    public function describe(AuditSummary $summary): void
    {
        $this->summary = $summary;
    }

    public function summary(): ?AuditSummary
    {
        return $this->summary;
    }
}
