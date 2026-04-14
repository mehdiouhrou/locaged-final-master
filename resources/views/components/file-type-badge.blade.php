@props([
    'path' => null,
    'extension' => null,
])

@php
    $ext = $extension ? strtolower((string) $extension) : ($path ? strtolower(pathinfo((string) $path, PATHINFO_EXTENSION)) : '');
    $variant = 'misc';
    $label = 'FILE';

    if ($ext === 'pdf') {
        $variant = 'pdf';
        $label = 'PDF';
    } elseif (in_array($ext, ['doc', 'docx', 'odt', 'rtf'], true)) {
        $variant = 'doc';
        $label = 'DOC';
    } elseif (in_array($ext, ['xls', 'xlsx', 'csv'], true)) {
        $variant = 'xls';
        $label = 'XLS';
    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tif', 'tiff', 'heic'], true)) {
        $variant = 'img';
        $label = 'IMG';
    } elseif ($ext !== '') {
        $label = strtoupper(strlen($ext) > 4 ? substr($ext, 0, 4) : $ext);
    }
@endphp

<span {{ $attributes->merge(['class' => 'lgv2-file-badge lgv2-file-badge--' . $variant]) }}>{{ $label }}</span>
