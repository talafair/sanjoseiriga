@extends('layouts.app')

@section('title', 'Spin the Wheel')

@section('content')

    {{-- Header --}}
    <div class="page-header p-3 p-lg-4 mb-3">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col">
                <h2 class="h4 fw-bold mb-1"><i class="bi bi-stars me-2"></i>Spin the Wheel</h2>
                <p class="small mb-0 opacity-75">Try your luck and win XP!</p>
            </div>
            <div class="col-auto">
                <span class="badge bg-white text-yg rounded-pill px-2 py-1">
                    <i class="bi bi-star-fill me-1"></i><span id="userPoints">{{ number_format(auth()->user()->points) }}</span> pts
                </span>
            </div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Wheel --}}
        <div class="col-lg-7">
            <div class="card yg-card p-4 p-lg-5 text-center h-100">
                <div class="wheel-box my-3">
                    <span class="wheel-pointer" aria-hidden="true"></span>
                    <div class="wheel" id="wheel" role="img" aria-label="Prize wheel">
                        {{-- labels positioned by JS --}}
                    </div>
                    <div class="wheel-hub"><i class="bi bi-stars"></i></div>
                </div>

                <button class="btn btn-yg btn-lg px-5 py-3 mx-auto mt-4" id="spinBtn" style="max-width: 260px;" {{ $spunToday && $extraChances < 1 ? 'disabled' : '' }}>
                    <i class="bi bi-play-circle-fill me-2"></i>SPIN NOW
                </button>
                <p class="small text-secondary mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    @if ($spunToday && $extraChances < 1)
                        Daily spin used. Complete an official-issued task for another chance.
                    @elseif ($spunToday)
                        Daily spin used. {{ $extraChances }} task-issued {{ Str::plural('chance', $extraChances) }} remaining.
                    @else
                        One free spin is available today{{ $extraChances ? ', plus ' . $extraChances . ' task-issued ' . Str::plural('chance', $extraChances) : '' }}.
                    @endif
                </p>
            </div>
        </div>

        {{-- Prizes + recent winners --}}
        <div class="col-lg-5 d-flex flex-column gap-4">
            <div class="card yg-card">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-gift me-2 text-yg"></i>Prize List</span>
                    @if (auth()->user()->isOfficial())
                        <a href="{{ route('prizes.index') }}" class="btn btn-yg-outline btn-sm">
                            <i class="bi bi-pencil-square me-1"></i>Manage
                        </a>
                    @endif
                </div>
                <div class="card-body p-3">
                    @foreach ($prizes as $prize)
                        <div class="prize-row">
                            <span class="prize-dot" style="background: {{ $prize->color }};"></span>
                            <i class="bi {{ $prize->icon }} text-secondary"></i>
                            <span class="fw-semibold small">{{ $prize->label }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="card yg-card flex-grow-1">
                <div class="card-header py-3"><i class="bi bi-people me-2 text-yg"></i>Recent Winners</div>
                <div class="card-body p-3">
                    @forelse ($recentWinners as $win)
                        <div class="d-flex align-items-center gap-3 p-2 {{ !$loop->first ? 'border-top' : '' }}">
                            <img src="{{ $win->user->avatar_url }}" alt="{{ $win->user->name }}" class="avatar avatar-sm object-fit-cover">
                            <div class="flex-grow-1">
                                <div class="fw-semibold small">{{ $win->user->name }}</div>
                                <div class="text-secondary" style="font-size: .75rem;">{{ $win->created_at->diffForHumans() }}</div>
                            </div>
                            <span class="badge badge-soft rounded-pill">{{ $win->prize_label }}</span>
                        </div>
                    @empty
                        <p class="text-center text-secondary small py-4 mb-0">
                            <i class="bi bi-emoji-smile fs-4 d-block mb-1"></i>No winners yet — be the first to spin!
                        </p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Prize modal --}}
    <div class="modal fade" id="prizeModal" tabindex="-1" aria-labelledby="prizeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0" style="border-radius: 20px;">
                <div class="modal-body text-center p-5">
                    <div class="achievement-badge icon-gold mx-auto mb-4 prize-pop" style="width: 100px; height: 100px; font-size: 2.6rem;">
                        <i class="bi bi-gift-fill" id="prizeModalIcon"></i>
                    </div>
                    <h3 class="fw-bold mb-2" id="prizeModalLabel">Congratulations! 🎉</h3>
                    <p class="text-secondary mb-1" id="prizeModalLead">You won</p>
                    <p class="fs-3 fw-bold text-yg mb-4" id="prizeModalPrize">—</p>
                    <button type="button" class="btn btn-yg px-5 py-2" data-bs-dismiss="modal">
                        <i class="bi bi-check-circle me-2"></i>Collect Reward
                    </button>
                </div>
            </div>
        </div>
    </div>

    <canvas id="confettiCanvas" style="position: fixed; inset: 0; width: 100vw; height: 100vh; pointer-events: none; z-index: 2000;"></canvas>

@endsection

@push('scripts')
<script>
    (function () {
        const SEGMENTS = @json($wheelSegments);
        const SEG_ANGLE = 360 / SEGMENTS.length;

        const wheel = document.getElementById('wheel');
        const spinBtn = document.getElementById('spinBtn');
        let rotation = 0;
        let spinning = false;

        // Paint the wheel from the same segment list the server uses.
        wheel.style.background = 'conic-gradient(' + SEGMENTS.map((s, i) =>
            `${s.color} ${i * SEG_ANGLE}deg ${(i + 1) * SEG_ANGLE}deg`
        ).join(', ') + ')';

        // Labels — each rotated to the middle of its segment.
        SEGMENTS.forEach((seg, i) => {
            const label = document.createElement('span');
            label.className = 'wheel-label' + (seg.light ? ' light' : '');
            label.textContent = seg.label;
            label.style.transform = `rotate(${i * SEG_ANGLE + SEG_ANGLE / 2 - 90}deg)`;
            wheel.appendChild(label);
        });

        // Confetti (lightweight, no dependency)
        const canvas = document.getElementById('confettiCanvas');
        const ctx = canvas.getContext('2d');
        let particles = [];
        let confettiRunning = false;

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }
        resizeCanvas();
        window.addEventListener('resize', resizeCanvas);

        function launchConfetti() {
            const colors = ['#9ACD32', '#7CB342', '#FBC02D', '#FFF176', '#558B2F', '#F9A825'];
            for (let i = 0; i < 160; i++) {
                particles.push({
                    x: Math.random() * canvas.width,
                    y: -20 - Math.random() * canvas.height * 0.5,
                    w: 6 + Math.random() * 6,
                    h: 8 + Math.random() * 8,
                    vy: 2 + Math.random() * 3.5,
                    vx: -1.5 + Math.random() * 3,
                    rot: Math.random() * Math.PI * 2,
                    vr: -0.12 + Math.random() * 0.24,
                    color: colors[Math.floor(Math.random() * colors.length)],
                });
            }
            if (!confettiRunning) {
                confettiRunning = true;
                requestAnimationFrame(tickConfetti);
            }
        }

        function tickConfetti() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            particles = particles.filter(p => p.y < canvas.height + 30);
            particles.forEach(p => {
                p.x += p.vx;
                p.y += p.vy;
                p.rot += p.vr;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            });
            if (particles.length > 0) {
                requestAnimationFrame(tickConfetti);
            } else {
                confettiRunning = false;
                ctx.clearRect(0, 0, canvas.width, canvas.height);
            }
        }

        // Spin — the server decides the prize and saves it, we just animate to it.
        const prizeModal = new bootstrap.Modal(document.getElementById('prizeModal'));
        const prizeModalPrize = document.getElementById('prizeModalPrize');
        const prizeModalIcon = document.getElementById('prizeModalIcon');
        const prizeModalLabel = document.getElementById('prizeModalLabel');
        const prizeModalLead = document.getElementById('prizeModalLead');

        spinBtn.addEventListener('click', async () => {
            if (spinning) return;
            spinning = true;
            spinBtn.disabled = true;
            spinBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Spinning...';

            let result;
            try {
                const response = await fetch(@json(route('spin.play')), {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @json(csrf_token()),
                        'Accept': 'application/json',
                    },
                });
                if (!response.ok) throw new Error('Spin failed');
                result = await response.json();
            } catch (e) {
                spinning = false;
                spinBtn.disabled = false;
                spinBtn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>SPIN NOW';
                alert('Something went wrong — please try again.');
                return;
            }

            // Rotate so the middle of the winning segment lands under the top pointer.
            const winnerMid = result.index * SEG_ANGLE + SEG_ANGLE / 2;
            const target = 360 * (5 + Math.floor(Math.random() * 3)) + (360 - winnerMid);
            rotation += target - (rotation % 360);
            wheel.style.transform = `rotate(${rotation}deg)`;

            setTimeout(() => {
                spinning = false;
                spinBtn.disabled = result.extraChances < 1;
                spinBtn.innerHTML = '<i class="bi bi-play-circle-fill me-2"></i>SPIN NOW';

                document.getElementById('userPoints').textContent = Number(result.points).toLocaleString();

                const won = result.type !== 'none';
                prizeModalLabel.textContent = won ? 'Congratulations! 🎉' : 'So close!';
                prizeModalLead.textContent = won ? 'You won' : 'Better luck next time';
                prizeModalPrize.textContent = result.label;
                prizeModalIcon.className = 'bi ' + result.icon;
                if (won) launchConfetti();
                prizeModal.show();
            }, 5200);
        });
    })();
</script>
@endpush
