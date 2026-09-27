<?php

namespace App\Enums;

enum DocumentStatus: string
{
    case Pending      = 'pending';
    case Declined     = 'declined';
    case Approved     = 'approved';
    case Archived     = 'archived';
    case Destroyed    = 'destroyed';
    case Brouillon    = 'brouillon';
    case EnRelecture  = 'en_relecture';
    case Valide       = 'valide';
    case AttenteArchivage = 'attente_archivage';

    /**
     * Get only the active statuses for filters (excluding archived/destroyed)
     */
    public static function activeCases(): array
    {
        $cases = [
            self::Pending,
            self::Declined,
            self::Approved,
        ];

        if (\App\Support\Branding::isCollaborativeModuleEnabled()) {
            $cases[] = self::Brouillon;
            $cases[] = self::EnRelecture;
            $cases[] = self::Valide;
            $cases[] = self::AttenteArchivage;
        }

        return $cases;
        // Note: 'expired' is handled separately via is_expired flag, not this enum
    }

    /**
     * Statuts du flux collaboratif (avant assignation catégorie et entrée dans le pipeline archive)
     */
    public static function collaborativeCases(): array
    {
        return [
            self::Brouillon,
            self::EnRelecture,
            self::Valide,
        ];
    }

    /**
     * Virtual status for expired documents filter
     */
    public const EXPIRED = 'expired';
}
