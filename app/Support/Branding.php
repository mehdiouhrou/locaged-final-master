<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Branding
{
    protected static function storagePath(): string
    {
        return storage_path('app/branding.json');
    }

    protected static function read(): array
    {
        $path = self::storagePath();
        if (! file_exists($path)) {
            return [];
        }
        $json = file_get_contents($path);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    protected static function write(array $data): void
    {
        $path = self::storagePath();
        @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    public static function set(string $key, string $relativePublicPath): void
    {
        $data = self::read();
        $data[$key] = $relativePublicPath; // e.g., branding/your-file.jpg (disk=public)
        self::write($data);
    }

    /**
     * Build a URL for a file under public/ with path segments URL-encoded (spaces, etc.),
     * so nginx and browsers resolve names like "Logo 3.svg" reliably.
     */
    protected static function safePublicAssetUrl(string $relativePath): string
    {
        $relativePath = trim($relativePath, '/');
        if ($relativePath === '') {
            return asset('');
        }

        $segments = explode('/', $relativePath);

        return asset(implode('/', array_map('rawurlencode', $segments)));
    }

    public static function get(string $key, ?string $defaultRelativeAsset = null): ?string
    {
        $data = self::read();
        $val = $data[$key] ?? null;
        if ($val) {
            $val = ltrim((string) $val, '/');

            // Allow absolute URLs directly.
            if (Str::startsWith($val, ['http://', 'https://'])) {
                return $val;
            }

            // If an asset path was stored, serve it directly from public assets.
            if (Str::startsWith($val, 'assets/')) {
                return self::safePublicAssetUrl($val);
            }

            // Stored as path relative to public disk: ensure file exists before using it.
            if (Storage::disk('public')->exists($val)) {
                return asset('storage/' . $val);
            }

            // Stored as path relative to public disk, build full URL
            // but fallback below if the file is missing.
        }
        if ($defaultRelativeAsset) {
            return self::safePublicAssetUrl($defaultRelativeAsset);
        }
        return null;
    }

    public static function headerLogoUrl(): string
    {
        return (string) (self::get('header_logo', 'assets/Logo 3.svg') ?? self::safePublicAssetUrl('assets/Logo 3.svg'));
    }

    /**
     * URL du logo LocaGed affiché dans la sidebar (texte stylisé par défaut si non configuré).
     * Retourne null si aucun logo n'a été uploadé (la vue doit alors afficher le texte stylisé).
     */
    public static function sidebarLogoUrl(): ?string
    {
        return self::get('sidebar_logo');
    }

    /**
     * Chemin absolu d’un fichier logo lisible par mPDF (console Master / branding.json).
     */
    public static function clientLogoAbsolutePathForPdf(): ?string
    {
        $data = self::read();
        $val = $data['header_logo'] ?? null;
        if (! $val) {
            $fallback = public_path('assets/Logo 3.svg');

            return is_file($fallback) ? $fallback : null;
        }

        $val = ltrim((string) $val, '/');
        if (Str::startsWith($val, ['http://', 'https://'])) {
            return null;
        }

        if (Str::startsWith($val, 'assets/')) {
            $p = public_path($val);

            return is_file($p) ? $p : null;
        }

        if (Storage::disk('public')->exists($val)) {
            return Storage::disk('public')->path($val);
        }

        return null;
    }

    /**
     * Data-URI pour intégration fiable dans un PDF (sans requête HTTP).
     */
    public static function clientLogoDataUriForPdf(): ?string
    {
        $path = self::clientLogoAbsolutePathForPdf();
        if (! $path || ! is_readable($path)) {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';
        $raw = @file_get_contents($path);
        if ($raw === false) {
            return null;
        }

        return 'data:'.$mime.';base64,'.base64_encode($raw);
    }

    public static function loginImageUrl(): string
    {
        return self::resolveLoginCoverUrl();
    }

    /**
     * Image de couverture login : chemin racine (/assets/…, /storage/…) pour éviter
     * un APP_URL différent de l’hôte réel (ex. .env en prod + test en 127.0.0.1).
     * URLs http(s) absolues conservées (CDN / autre domaine volontaire).
     */
    public static function resolveLoginCoverUrl(): string
    {
        $data = self::read();
        $val = isset($data['login_left_image']) ? trim((string) $data['login_left_image']) : '';

        if ($val !== '') {
            $val = ltrim($val, '/');

            if (Str::startsWith($val, ['http://', 'https://'])) {
                return $val;
            }

            if (Str::startsWith($val, 'assets/')) {
                if (is_file(public_path($val))) {
                    return '/'.$val;
                }
            } elseif (Storage::disk('public')->exists($val)) {
                return '/storage/'.$val;
            }
        }

        foreach (['assets/cbanner.jpg', 'assets/bg.jpg'] as $fallback) {
            if (is_file(public_path($fallback))) {
                return '/'.$fallback;
            }
        }

        return '/assets/bg.jpg';
    }

    /**
     * Get the maximum number of users allowed in the system.
     * Returns 0 if no limit is set (unlimited users).
     */
    public static function getMaxUsers(): int
    {
        $data = self::read();
        return (int) ($data['max_users'] ?? 0);
    }

    /**
     * Set the maximum number of users allowed in the system.
     * Set to 0 for unlimited users.
     */
    public static function setMaxUsers(int $maxUsers): void
    {
        $data = self::read();
        $data['max_users'] = $maxUsers;
        self::write($data);
    }

    /**
     * Check if the user limit has been reached.
     * Returns true if the current user count equals or exceeds the maximum allowed.
     */
    public static function isUserLimitReached(): bool
    {
        $maxUsers = self::getMaxUsers();
        if ($maxUsers <= 0) {
            return false; // No limit set
        }
        
        $currentUserCount = \App\Models\User::count();
        return $currentUserCount >= $maxUsers;
    }

    /**
     * Get the number of remaining user slots available.
     * Returns -1 if unlimited users are allowed.
     * Returns 0 or positive number if there's a limit.
     */
    public static function getRemainingUserSlots(): int
    {
        $maxUsers = self::getMaxUsers();
        if ($maxUsers <= 0) {
            return -1; // Unlimited
        }
        
        $currentUserCount = \App\Models\User::count();
        return max(0, $maxUsers - $currentUserCount);
    }

    /**
     * Get the application timezone.
     * Returns UTC if no timezone is set.
     */
    public static function getTimezone(): string
    {
        $data = self::read();
        return $data['timezone'] ?? 'UTC';
    }

    /**
     * Set the application timezone.
     */
    public static function setTimezone(string $timezone): void
    {
        $data = self::read();
        $data['timezone'] = $timezone;
        self::write($data);
    }

    /**
     * Top-most organization node label shown in structures tree.
     */
    public static function getOrgRootName(): string
    {
        $data = self::read();
        $name = trim((string) ($data['org_root_name'] ?? ''));

        return $name !== '' ? $name : 'Direction Générale';
    }

    public static function setOrgRootName(string $name): void
    {
        $data = self::read();
        $clean = trim($name);
        $data['org_root_name'] = $clean !== '' ? $clean : 'Direction Générale';
        self::write($data);
    }

    /**
     * Module "Document actif" (workflow collaboratif) : désactivé par défaut,
     * activable par instance via Console Master (master uniquement).
     */
    public static function isCollaborativeModuleEnabled(): bool
    {
        $data = self::read();
        return (bool) ($data['collaborative_module_enabled'] ?? false);
    }

    public static function setCollaborativeModuleEnabled(bool $enabled): void
    {
        $data = self::read();
        $data['collaborative_module_enabled'] = $enabled;
        self::write($data);
    }
}


