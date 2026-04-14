<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Document list visibility driver (Document global scope)
    |--------------------------------------------------------------------------
    |
    | legacy — historical role-based filtering on document org fields.
    | profile — restrict documents by category_id / subcategory linked to ProfileCategoryAccessService.
    |
    | Category rows are not org-scoped; upload sets document department/service from the user account.
    |
    */

    'category_access_driver' => env('GED_CATEGORY_ACCESS_DRIVER', 'profile'),

    /*
    |--------------------------------------------------------------------------
    | File d'attente OCR (Horizon / Redis)
    |--------------------------------------------------------------------------
    |
    | Nom de la queue Laravel pour ProcessOcrJob. En prod Redis + Horizon,
    | déclarer un superviseur qui écoute ce nom (voir docs/deployment-search-horizon.md).
    |
    */
    'ocr_queue' => env('OCR_QUEUE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Workflow rules — enforcement
    |--------------------------------------------------------------------------
    |
    | La table workflow_rules accepte category_id (wildcard si null) + level.
    | Si la table contient au moins une règle pour le département du document, les
    | transitions sans règle correspondante sont refusées lorsque ce flag est true.
    |
    */
    'enforce_workflow_rules' => env('GED_ENFORCE_WORKFLOW_RULES', false),

];
