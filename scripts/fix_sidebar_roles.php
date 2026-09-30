<?php
/**
 * Déplace le lien "Rôles" de la section "Outils LocaGed" (master only)
 * vers la section "Administration" (protégé par @can('view any role')).
 */

$file = base_path('resources/views/layouts/sidebar.blade.php');
$content = file_get_contents($file);

// ── 1. Insérer le lien Rôles dans Administration, juste avant @endcanany final ──
// On cherche le bloc Storage + @endcanany qui ferme le sous-menu Administration
$search1 = <<<'NEEDLE'
                @canany(['view organization wide reports', 'view any role'])
                <li class="mt-2">
                    <a href="{{ route('storage.overview') }}" class="{{ request()->routeIs('storage.overview') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/Frame.svg') }}" class="me-2" />
                        <span class="sidebar-text">Storage &amp; Server Space</span>
                    </a>
                </li>
                @endcanany
NEEDLE;

$replace1 = <<<'REPLACEMENT'
                @canany(['view organization wide reports', 'view any role'])
                <li class="mt-2">
                    <a href="{{ route('storage.overview') }}" class="{{ request()->routeIs('storage.overview') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/Frame.svg') }}" class="me-2" />
                        <span class="sidebar-text">Storage &amp; Server Space</span>
                    </a>
                </li>
                @endcanany
                @can('view any role')
                <li class="mt-2">
                    <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/lock2.svg') }}" class="me-2" alt="" />
                        <span class="sidebar-text">{{ __('Rôles') }}</span>
                    </a>
                </li>
                @endcan
REPLACEMENT;

if (str_contains($content, $search1)) {
    $content = str_replace($search1, $replace1, $content);
    echo "✓ Lien Rôles ajouté dans Administration\n";
} else {
    echo "✗ Bloc Administration non trouvé — vérifier le fichier\n";
    exit(1);
}

// ── 2. Supprimer le lien Rôles du bloc @role('master') ──
$search2 = <<<'NEEDLE'
                <li class="mt-2">
                    <a href="{{ route('roles.index') }}" class="{{ request()->routeIs('roles.*') ? 'active' : '' }}">
                        <img src="{{ asset('assets/template/lock2.svg') }}" class="me-2" alt="" />
                        <span class="sidebar-text">{{ __('Rôles') }}</span>
                    </a>
                </li>
NEEDLE;

if (str_contains($content, $search2)) {
    $content = str_replace($search2, '', $content);
    echo "✓ Lien Rôles retiré du bloc master\n";
} else {
    echo "! Lien master déjà retiré ou non trouvé (OK si déjà fait)\n";
}

// ── 3. Mettre à jour le has-submenu actif du bloc master (retirer roles.*) ──
$search3 = 'request()->routeIs(\'master.console\') || request()->routeIs(\'roles.*\') || request()->routeIs(\'ocr-jobs.*\')';
$replace3 = 'request()->routeIs(\'master.console\') || request()->routeIs(\'ocr-jobs.*\')';

if (str_contains($content, $search3)) {
    $content = str_replace($search3, $replace3, $content);
    echo "✓ Classe active du bloc master mise à jour\n";
}

file_put_contents($file, $content);
echo "\nFichier sauvegardé. OK\n";
