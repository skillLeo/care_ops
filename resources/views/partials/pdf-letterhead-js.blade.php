@php
    $letterheadLandscapePath = storage_path('app/private/templates/Dashboard Letterhead Landscape.png');
    $letterheadPortraitPath = storage_path('app/private/templates/Dashboard Letterhead Portrait.png');
    $letterheadLandscapeData = file_exists($letterheadLandscapePath)
        ? base64_encode(file_get_contents($letterheadLandscapePath))
        : '';
    $letterheadPortraitData = file_exists($letterheadPortraitPath)
        ? base64_encode(file_get_contents($letterheadPortraitPath))
        : '';
@endphp
const pdfLetterheadLandscape = @json($letterheadLandscapeData ? "data:image/png;base64,{$letterheadLandscapeData}" : '');
const pdfLetterheadPortrait = @json($letterheadPortraitData ? "data:image/png;base64,{$letterheadPortraitData}" : '');

const resolvePdfLetterhead = (orientation = 'portrait') => {
    return orientation === 'landscape' ? pdfLetterheadLandscape : pdfLetterheadPortrait;
};

const applyPdfLetterhead = (doc, orientation = 'portrait') => {
    const image = resolvePdfLetterhead(orientation);

    if (!image) {
        return;
    }

    doc.pageSize = 'LETTER';
    doc.background = function (currentPage, pageSize) {
        return {
            image: image,
            width: pageSize.width,
            height: pageSize.height
        };
    };
};
