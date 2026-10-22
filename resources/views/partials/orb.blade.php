@php
    $variant = $variant ?? 'sphere';
    $spot = $spot ?? 'hero';
    $speed = $speed ?? '26s';
@endphp

<div class="scene3d scene3d--{{ $spot }}" aria-hidden="true">
    <div class="orb3d orb3d--{{ $variant }}" style="--orb-speed: {{ $speed }}">
        @if ($variant === 'cube' || $variant === 'nested')
            @php
                $shells = $variant === 'nested'
                    ? [['orb3d__shell--inner', '17s'], ['orb3d__shell--outer', '34s']]
                    : [['orb3d__shell--outer', '28s']];
                $faces = [
                    'translateZ(var(--half))',
                    'rotateY(180deg) translateZ(var(--half))',
                    'rotateY(90deg) translateZ(var(--half))',
                    'rotateY(-90deg) translateZ(var(--half))',
                    'rotateX(90deg) translateZ(var(--half))',
                    'rotateX(-90deg) translateZ(var(--half))',
                ];
            @endphp

            @foreach ($shells as [$shellClass, $shellSpeed])
                <div class="orb3d__shell {{ $shellClass }}" style="--shell-speed: {{ $shellSpeed }}">
                    @foreach ($faces as $face)
                        <span class="orb3d__face" style="transform: {{ $face }}"></span>
                    @endforeach
                </div>
            @endforeach
        @elseif ($variant === 'rings')
            <div class="orb3d__spin">
                @foreach ([
                    'rotateX(76deg)',
                    'rotateX(76deg) rotateY(60deg) scale(0.82)',
                    'rotateX(76deg) rotateY(-60deg) scale(0.82)',
                    'rotateX(18deg) rotateY(20deg) scale(0.68)',
                    'rotateX(-42deg) rotateY(-30deg) scale(0.54)',
                ] as $ring)
                    <span class="orb3d__ring" style="transform: {{ $ring }}"></span>
                @endforeach
                <span class="orb3d__sat"></span>
            </div>
        @else
            <div class="orb3d__spin">
                @foreach ([0, 26, 52, 78, 104, 130, 156] as $angle)
                    <span class="orb3d__ring" style="transform: rotateY({{ $angle }}deg)"></span>
                @endforeach
                <span class="orb3d__sat"></span>
            </div>
            <div class="orb3d__lat">
                @foreach ([['-32%', 0.46], ['0%', 1], ['32%', 0.46]] as [$offset, $scale])
                    <span class="orb3d__ring orb3d__ring--lat" style="transform: translateY({{ $offset }}) rotateX(74deg) scale({{ $scale }})"></span>
                @endforeach
            </div>
        @endif

        <span class="orb3d__core"></span>
    </div>
</div>
