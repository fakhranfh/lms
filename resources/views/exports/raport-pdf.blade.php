<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1a1a1a; }
        h1 { font-size: 18px; margin-bottom: 18px; }
        .course { margin-bottom: 22px; page-break-inside: avoid; }
        .course h2 { font-size: 14px; margin-bottom: 6px; background-color: #f2f2f2; padding: 6px 8px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        th, td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        th { background-color: #f8f8f8; }
        .text-right { text-align: right; }
        .final-row td { font-weight: bold; background-color: #fafafa; }
    </style>
</head>
<body>
    <h1>Raport — {{ $student->name }}</h1>

    @forelse ($courses as $entry)
        <div class="course">
            <h2>{{ $entry['course']->title }}</h2>
            <table>
                <thead>
                    <tr>
                        <th>Assessment Type</th>
                        <th class="text-right">Weight</th>
                        <th class="text-right">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($entry['typeRows'] as $typeRow)
                        <tr>
                            <td>{{ $typeRow['label'] }}</td>
                            <td class="text-right">{{ $typeRow['weight'] !== null ? number_format($typeRow['weight'], 0).'%' : '—' }}</td>
                            <td class="text-right">{{ $typeRow['score'] !== null ? number_format($typeRow['score'], 0) : '—' }}</td>
                        </tr>
                    @endforeach
                    <tr class="final-row">
                        <td>Final Score</td>
                        <td class="text-right">100%</td>
                        <td class="text-right">{{ $entry['finalScore'] !== null ? number_format($entry['finalScore'], 0) : '—' }} ({{ $entry['finalGrade'] ?? '—' }})</td>
                    </tr>
                </tbody>
            </table>
        </div>
    @empty
        <p>No courses found.</p>
    @endforelse
</body>
</html>
