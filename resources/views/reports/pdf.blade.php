@php
    $sections = [];
    $current  = null;

    foreach ($csvRows as $row) {
        $isEmpty = empty(array_filter($row, fn($c) => $c !== '' && $c !== null));

        if ($isEmpty) {
            if ($current) {
                $sections[] = $current;
                $current    = null;
            }
            continue;
        }

        if ($current === null) {
            $current = ['title' => $row[0], 'header' => null, 'rows' => []];
        } elseif ($current['header'] === null) {
            $current['header'] = $row;
        } else {
            $current['rows'][] = $row;
        }
    }

    if ($current) $sections[] = $current;

    // ✅ Split first section (summary) from the rest
    $summarySection  = $sections[0] ?? null;
    $dataSections    = array_slice($sections, 1);
@endphp

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body        { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1          { font-size: 18px; margin-bottom: 4px; }
        p.meta      { font-size: 11px; color: #777; margin-bottom: 16px; }

        /* Summary table */
        .summary-table          { width: 40%; border-collapse: collapse; margin-bottom: 24px; }
        .summary-table td       { padding: 5px 10px; border: 1px solid #ddd; }
        .summary-table td:first-child { font-weight: bold; background: #f5f5f5; }

        /* Section heading */
        h2          { font-size: 13px; margin: 20px 0 6px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }

        /* Data table */
        .data-table             { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .data-table th          { background: #4a4a4a; color: #fff; padding: 6px 8px; text-align: left; font-size: 11px; }
        .data-table td          { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 11px; }
        .data-table tr:nth-child(even) td { background: #fafafa; }

        .no-data    { color: #999; font-style: italic; font-size: 11px; }
    </style>
</head>
<body>
{{-- ── Summary ── --}}
@if ($summarySection)
    <h2>{{ $summarySection['title'] }}</h2>
    <table class="summary-table">
        @foreach ($summarySection['rows'] as $row)
            <tr>
                <td>{{ $row[0] ?? '' }}</td>
                <td>{{ $row[1] ?? '' }}</td>
            </tr>
        @endforeach
    </table>
@endif

{{-- ── Data Sections ── --}}
@foreach ($dataSections as $section)
    <h2>{{ $section['title'] }}</h2>

    @if (!empty($section['rows']))
        <table class="data-table">
            @if ($section['header'])
                <thead>
                    <tr>
                        @foreach ($section['header'] as $col)
                            <th>{{ $col }}</th>
                        @endforeach
                    </tr>
                </thead>
            @endif
            <tbody>
                @foreach ($section['rows'] as $row)
                    <tr>
                        @foreach ($row as $cell)
                            <td>{{ $cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p class="no-data">No data available.</p>
    @endif
@endforeach

</body>
</html>