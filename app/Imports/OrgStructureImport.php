<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Service;
use App\Models\SubDepartment;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

/**
 * Feuille Excel : colonne A = Pôle, B = Département (sous-structure), C = Service.
 * Première ligne = en-têtes (ignorée). Crée les enregistrements manquants.
 */
class OrgStructureImport implements ToCollection
{
    public function collection(Collection $rows): void
    {
        $first = true;
        foreach ($rows as $row) {
            if ($first) {
                $first = false;

                continue;
            }
            $deptName = trim((string) ($row[0] ?? ''));
            $subName = trim((string) ($row[1] ?? ''));
            $svcName = trim((string) ($row[2] ?? ''));
            if ($deptName === '') {
                continue;
            }

            $dept = Department::firstOrCreate(
                ['name' => $deptName],
                ['description' => null]
            );

            if ($subName === '') {
                continue;
            }

            $sub = SubDepartment::firstOrCreate([
                'department_id' => $dept->id,
                'name' => $subName,
            ]);

            if ($svcName === '') {
                continue;
            }

            Service::firstOrCreate([
                'sub_department_id' => $sub->id,
                'name' => $svcName,
            ]);
        }
    }
}
