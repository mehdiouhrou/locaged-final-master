<?php

namespace App\Services;

use App\Models\Document;

/**
 * Capture d’emplacement physique au moment d’une approbation de destruction (PV).
 */
class PhysicalLocationSnapshot
{
    /**
     * @return array{type: string, room?: string, row?: string, shelf?: string, box?: string}
     */
    public static function forDocument(Document $document): array
    {
        $document->loadMissing(['box.shelf.row.room']);

        // Dès qu’une boîte est renseignée, l’emplacement physique prime (même si metadata.digital_only est resté à true).
        if (! $document->box_id) {
            return ['type' => 'numerique'];
        }

        $box = $document->box;
        if (! $box) {
            return ['type' => 'numerique'];
        }

        $box->loadMissing('shelf.row.room');
        $shelf = $box->shelf;
        $row = $shelf?->row;
        $room = $row?->room;

        return [
            'type' => 'physique',
            'room' => $room?->name,
            'row' => $row?->name,
            'shelf' => $shelf?->name,
            'box' => $box->name,
        ];
    }
}
