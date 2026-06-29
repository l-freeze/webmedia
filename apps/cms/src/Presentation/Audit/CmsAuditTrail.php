<?php

declare(strict_types=1);

namespace App\Cms\Presentation\Audit;

final class CmsAuditTrail
{
    public const ATTRIBUTE = 'cms.audit_trail';

    private ?CmsAuditSummary $summary = null;

    public function describe(CmsAuditSummary $summary): void
    {
        $this->summary = $summary;
    }

    public function summary(): ?CmsAuditSummary
    {
        return $this->summary;
    }
}
