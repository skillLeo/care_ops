@php
    $letterheadPath = storage_path('app/private/templates/Dashboard Letterhead Portrait.png');
    $letterheadData = file_exists($letterheadPath)
        ? base64_encode(file_get_contents($letterheadPath))
        : '';
    $letterheadUrl = $letterheadData ? "data:image/png;base64,{$letterheadData}" : '';
@endphp

@page {
    size: letter;
    margin: 0;
}

body {
    margin: 0;
    padding: 36px;
    background-image: url('{{ $letterheadUrl }}');
    background-repeat: no-repeat;
    background-position: center top;
    background-size: cover;
}
