@php
    $dayStart = 6 * 60;
    $dayEnd = 23 * 60;
    $daySpan = $dayEnd - $dayStart;

    $toMinutes = static function (string $time): int {
        [$hours, $minutes] = array_pad(explode(':', $time), 3, '0');

        return ((int) $hours) * 60 + ((int) $minutes);
    };

    $segments = array_values($segments ?? []);
@endphp

<div class="timeline" aria-hidden="true">
    <div class="timeline-track">
        @foreach ($segments as $segment)
            @php
                $start = min(max($dayStart, $toMinutes((string) $segment['start'])), $dayEnd);
                $end = min(max($start, $toMinutes((string) $segment['end'])), $dayEnd);
                $left = ($start - $dayStart) / $daySpan * 100;
                $width = ($end - $start) / $daySpan * 100;
            @endphp
            <span class="timeline-segment" style="left: {{ number_format($left, 2, '.', '') }}%; width: {{ number_format($width, 2, '.', '') }}%;"></span>
        @endforeach
    </div>

    <div class="timeline-scale">
        @foreach ([6, 9, 12, 15, 18, 21] as $hour)
            <span class="timeline-hour">{{ sprintf('%02d:00', $hour) }}</span>
        @endforeach
    </div>
</div>
