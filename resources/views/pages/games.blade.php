@extends('layouts.app')

@section('title', 'Games')

@section('content')
    @php
        $triviaClientThemes = $themes->keyBy('id')->map(function ($theme) {
            return [
                'base_points' => $theme->base_points,
                'questions' => collect($theme->questions)->values()->map(function ($question, $index) {
                    return [
                        'index' => $index,
                        'question' => $question['question'],
                        'choices' => $question['choices'],
                    ];
                })->values(),
                'due_at' => $theme->due_at?->toIso8601String(),
            ];
        });
    @endphp

    @php($canPlay = auth()->check())
    <div class="page-header p-4 p-lg-5 mb-4">
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col">
                <h2 class="fw-bold mb-1"><i class="bi bi-controller me-2"></i>Community Games</h2>
                <p class="mb-0 opacity-75">Play quick games and learn something about Barangay San Jose</p>
            </div>
            <div class="col-auto"><span class="badge bg-white text-yg rounded-pill px-3 py-2 fs-6">{{ $themes->count() }} trivia themes</span></div>
        </div>
    </div>

    @if ($canPlay && auth()->user()->isOfficial())
        <div class="card yg-card mb-4">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start gap-3 mb-3">
                    <div><h5 class="fw-bold mb-1"><i class="bi bi-lightbulb me-2 text-yg"></i>Add a trivia theme</h5><p class="small text-secondary mb-0">Publish your own questions for residents to play.</p></div>
                    <span class="badge badge-gold rounded-pill">Official</span>
                </div>
                <form id="triviaThemeForm" action="{{ route('games.themes.store') }}" method="POST">
                    @csrf
                    <div class="row g-3">
                        <div class="col-md-3"><label class="form-label fw-semibold" for="themeTitle">Theme title</label><input id="themeTitle" name="title" class="form-control" value="{{ old('title') }}" placeholder="Barangay history" required></div>
                        <div class="col-md-2"><label class="form-label fw-semibold" for="basePoints">Points per correct answer</label><input id="basePoints" name="base_points" type="number" min="1" max="100000" class="form-control" value="{{ old('base_points', 100) }}" required></div>
                        <div class="col-md-3"><label class="form-label fw-semibold" for="dueAt">Due date and time</label><input id="dueAt" name="due_at" type="datetime-local" class="form-control" value="{{ old('due_at') }}" required><div class="form-text">Answers and rankings appear after this time.</div></div>
                        <div class="col-md-7"><div class="trivia-editor-box"><div class="trivia-box-label mb-2">Question</div><input id="builderQuestion" class="form-control mb-3" placeholder="Type one question"><div class="trivia-box-label mb-2">Choices</div><div class="row g-2"><div class="col-md-4"><input id="builderCorrect" class="form-control" placeholder="Correct answer"></div><div class="col-md-4"><input id="builderWrongOne" class="form-control" placeholder="Incorrect answer"></div><div class="col-md-4"><input id="builderWrongTwo" class="form-control" placeholder="Incorrect answer"></div></div><button id="addTriviaQuestion" type="button" class="btn btn-outline-success btn-sm mt-3"><i class="bi bi-plus-circle me-1"></i>Add this question</button></div><input type="hidden" id="questionsJson" name="questions_json"><div id="questionList" class="small text-secondary mt-2">No questions added yet.</div></div>
                    </div>
                    <button class="btn btn-yg mt-3"><i class="bi bi-cloud-arrow-up me-2"></i>Publish theme</button>
                </form>
            </div>
        </div>
    @endif

    @if ($errors->any())<div class="alert alert-danger rounded-3">{{ $errors->first() }}</div>@endif

    <div class="row g-4 mb-4">
        <div class="col-lg-6"><div class="game-box card yg-card h-100"><div class="card-body p-4 d-flex flex-column"><span class="display-5 text-yg mb-3"><i class="bi bi-lightning-charge-fill"></i></span><h4 class="fw-bold">Catch the Star</h4><p class="text-secondary">Three daily stages: Easy, Moderate, and Difficult. Each lasts 20 seconds.</p><div id="starStage" class="small fw-semibold text-yg mb-2">Easy · x1</div><div class="d-flex justify-content-between small text-secondary mb-2"><span>Time <strong id="starTime">20</strong>s</span><span>Score <strong id="starScore">0</strong></span></div><div id="starBoard" class="position-relative rounded-3 bg-light border flex-grow-1" style="min-height: 220px; overflow: hidden;"><button id="star" class="btn btn-warning position-absolute d-none" style="width: 48px;height: 48px;padding:0;"><i class="bi bi-star-fill"></i></button><span id="starMessage" class="position-absolute top-50 start-50 translate-middle text-secondary small text-center">Press start to play</span></div><div id="starFinalScore" class="final-score-box mt-3">Final Score: Not played</div><button id="starStart" class="btn btn-yg mt-3" {{ (!$canPlay || $todayRuns->contains('catch-star')) ? 'disabled' : '' }}><i class="bi bi-play-fill me-1"></i>{{ !$canPlay ? 'Sign in to play' : ($todayRuns->contains('catch-star') ? 'Played today' : 'Start 3 stages') }}</button></div></div></div>
        <div class="col-lg-6"><div class="game-box card yg-card h-100"><div class="card-body p-4 d-flex flex-column"><span class="display-5 text-yg mb-3"><i class="bi bi-grid-3x3-gap-fill"></i></span><h4 class="fw-bold">Memory Test</h4><p class="text-secondary">Three daily stages: Easy, Moderate, and Difficult. More tiles appear each stage.</p><div id="memoryStage" class="small fw-semibold text-yg mb-2">Easy · x1</div><div class="d-flex justify-content-between small text-secondary mb-2"><span>Time <strong id="memoryTime">20</strong>s</span><span>Pairs <strong id="memoryPairs">0</strong></span><span>Turns <strong id="memoryTurns">0</strong></span></div><div id="memoryBoard" class="memory-board flex-grow-1"></div><div id="memoryFinalScore" class="final-score-box mt-3">Final Score: Not played</div><button id="memoryStart" class="btn btn-yg mt-3" {{ (!$canPlay || $todayRuns->contains('memory-test')) ? 'disabled' : '' }}><i class="bi bi-play-fill me-1"></i>{{ !$canPlay ? 'Sign in to play' : ($todayRuns->contains('memory-test') ? 'Played today' : 'Start 3 stages') }}</button></div></div></div>
        <div class="col-12"><div class="game-box card yg-card"><div class="card-body p-4"><span class="display-5 text-yg mb-3"><i class="bi bi-patch-question-fill"></i></span><h4 class="fw-bold">Barangay Trivia</h4><p class="text-secondary">Earn points for every correct answer, plus a ranking bonus based on the perfect score.</p><div id="triviaArea">@if ($themes->isEmpty())<div class="text-secondary small py-4">No themes published yet.</div>@else<select id="themeSelect" class="form-select mb-3">@foreach ($themes as $theme)<option value="{{ $theme->id }}">{{ $theme->title }} · {{ $theme->base_points }} points per correct answer · due {{ $theme->due_at?->format('M j, Y g:i A') ?? 'not set' }}</option>@endforeach</select><section class="trivia-question-box mb-4" aria-labelledby="triviaQuestionLabel"><div id="triviaQuestionLabel" class="trivia-box-label">Question</div><div id="triviaQuestion" class="fw-semibold">Choose a theme and start.</div></section><section aria-labelledby="triviaAnswersLabel"><div id="triviaAnswersLabel" class="trivia-box-label mb-2">Answers</div><div id="triviaChoices" class="trivia-answers-grid"></div></section>@endif</div>@if ($themes->isNotEmpty())<div class="d-flex justify-content-between align-items-center mt-3"><span id="triviaScore" class="small text-secondary">Score 0/0</span><button id="triviaStart" class="btn btn-yg"><i class="bi bi-play-fill me-1"></i>Start</button></div>@endif</div></div></div>
    </div>

    <div class="card yg-card mb-4"><div class="card-header py-3"><i class="bi bi-broadcast me-2 text-yg"></i>Live game rankings</div><div class="card-body"><div class="row g-4"><div class="col-md-6"><h6 class="fw-bold">Catch the Star</h6><div class="table-responsive"><table class="table table-sm ranking-table align-middle mb-0"><thead><tr><th>Rank</th><th>Name</th><th>Total points</th><th>Last played</th></tr></thead><tbody id="starRankings"><tr><td colspan="4" class="text-secondary">No scores yet.</td></tr></tbody></table></div></div><div class="col-md-6"><h6 class="fw-bold">Memory Test</h6><div class="table-responsive"><table class="table table-sm ranking-table align-middle mb-0"><thead><tr><th>Rank</th><th>Name</th><th>Total points</th><th>Last played</th></tr></thead><tbody id="memoryRankings"><tr><td colspan="4" class="text-secondary">No scores yet.</td></tr></tbody></table></div></div></div></div></div>

    @if ($themes->isNotEmpty())
        <div class="card yg-card mb-4"><div class="card-header py-3 d-flex justify-content-between align-items-center"><span><i class="bi bi-bar-chart-fill me-2 text-yg"></i>Trivia rankings</span><form method="GET" class="d-flex align-items-center gap-2"><label for="rankingPeriod" class="small text-secondary">Period</label><select id="rankingPeriod" name="period" class="form-select form-select-sm" onchange="this.form.submit()"><option value="today" @selected($period === 'today')>Today</option><option value="weekly" @selected($period === 'weekly')>Weekly</option><option value="monthly" @selected($period === 'monthly')>Monthly</option><option value="all" @selected($period === 'all')>All time</option></select></form></div><div class="card-body border-bottom small text-secondary">Today shows each trivia theme separately. Longer periods show accumulated points across all published trivia.</div>@if ($period !== 'today')<div class="table-responsive"><table class="table align-middle mb-0 ranking-table"><thead><tr class="small text-secondary text-uppercase"><th class="ps-4">Rank</th><th>Resident</th><th>Total points</th><th class="pe-4">Last answer</th></tr></thead><tbody id="triviaAggregateRankings">@forelse ($triviaAggregateRankings as $answer)<tr><td class="ps-4"><span class="badge {{ $answer['rank'] <= 3 ? 'badge-gold' : 'badge-soft' }} rounded-pill">#{{ $answer['rank'] }}</span></td><td class="fw-semibold">{{ $answer['name'] }}</td><td>{{ $answer['points'] }}</td><td class="pe-4 text-secondary">{{ $answer['answered_at'] }}</td></tr>@empty<tr><td colspan="4" class="p-3 text-secondary">No answers were submitted.</td></tr>@endforelse</tbody></table></div>@else @foreach ($themes as $theme)@if ($canPlay && auth()->user()->isOfficial() || $theme->due_at?->isPast())<div class="px-3 pt-3"><h6 class="fw-bold mb-2">{{ $theme->title }}</h6><div class="table-responsive"><table class="table align-middle mb-0 ranking-table"><thead><tr class="small text-secondary text-uppercase"><th class="ps-4">Rank</th><th>Resident</th><th>Total points</th><th class="pe-4">Last answer</th></tr></thead><tbody id="triviaRankings{{ $theme->id }}">@forelse ($rankings->get($theme->id, collect()) as $answer)<tr><td class="ps-4"><span class="badge {{ $answer['rank'] <= 3 ? 'badge-gold' : 'badge-soft' }} rounded-pill">#{{ $answer['rank'] }}</span></td><td class="fw-semibold">{{ $answer['name'] }}</td><td>{{ $answer['points'] }}</td><td class="pe-4 text-secondary">{{ $answer['answered_at'] }}</td></tr>@empty<tr><td colspan="4" class="p-3 text-secondary">No answers were submitted.</td></tr>@endforelse</tbody></table></div></div>@else<div class="p-3 small text-secondary">{{ $theme->title }} results will be visible after {{ $theme->due_at?->format('M j, Y g:i A') ?? 'the official sets a due date' }}.</div>@endif @endforeach @endif</div>
    @endif

    @if (auth()->user()->isOfficial() && $themes->isNotEmpty())
        <div class="card yg-card"><div class="card-header py-3"><i class="bi bi-collection me-2 text-yg"></i>Published themes</div><div class="list-group list-group-flush">@foreach ($themes as $theme)<div class="list-group-item d-flex justify-content-between align-items-center px-4"><span><strong>{{ $theme->title }}</strong><span class="small text-secondary ms-2">{{ count($theme->questions) }} questions</span></span><form method="POST" action="{{ route('games.themes.destroy', $theme) }}" onsubmit="return confirm('Remove this trivia theme?');">@csrf @method('DELETE')<button class="btn btn-outline-danger btn-sm rounded-3" title="Remove theme"><i class="bi bi-trash"></i></button></form></div>@endforeach</div></div>
    @endif
@endsection

@push('styles')
<style>
    .game-box { border-top: 4px solid #9acd32; }
    .ranking-table { table-layout: fixed; width: 100%; }
    .ranking-table th, .ranking-table td { padding: .65rem .5rem; vertical-align: middle; }
    .ranking-table th:nth-child(1) { width: 16%; }
    .ranking-table th:nth-child(2) { width: 32%; }
    .ranking-table th:nth-child(3) { width: 20%; }
    .ranking-table th:nth-child(4) { width: 32%; }
    .ranking-table td:last-child { white-space: nowrap; font-size: .78rem; }
    .ranking-period-select { width: auto; min-width: 112px; }
    .final-score-box { border: 2px solid #9acd32; border-radius: 12px; background: #f1f7df; padding: .85rem 1rem; font-weight: 700; color: #5d7c25; }
    .trivia-question-box { border: 4px solid #9acd32; border-radius: 14px; padding: 1.5rem; margin-bottom: 2rem; min-height: 130px; background: #f1f7df; box-shadow: 0 4px 0 #dfe8c9; display:flex; flex-direction:column; justify-content:center; }
    .trivia-editor-box { border: 2px solid #9acd32; border-radius: 12px; padding: 1.25rem; background: #f7faef; }
    .trivia-box-label { color: #5d7c25; font-size: .75rem; font-weight: 700; letter-spacing: .05em; text-transform: uppercase; }
    .trivia-answers-grid { display:grid; grid-template-columns:repeat(3, minmax(0, 1fr)); gap:1rem; }
    .trivia-answer-box { border: 3px solid #dfe8c9; border-radius: 12px; padding: .75rem; background: #fff; min-height: 100px; box-shadow: 0 3px 0 #eef3df; display:flex; flex-direction:column; gap:.5rem; }
    .trivia-answer-label { color:#6c757d; font-size:.7rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
    .trivia-answer-box .trivia-choice { width:100%; height:100%; flex:1; border-width:2px; }
    @media (max-width: 767.98px) { .trivia-answers-grid { grid-template-columns:1fr; } }
    .memory-board { display:grid; grid-template-columns:repeat(4, 1fr); gap:.5rem; min-height:220px; align-content:start; }
    .memory-card { border:0; border-radius:8px; background:#e9f2d0; color:#5d7c25; font-size:1.5rem; min-height:62px; }
    .memory-card.revealed, .memory-card.matched { background:#9acd32; color:#fff; }
</style>
@endpush

@push('scripts')
<script>
(() => {
    const themes = @json($triviaClientThemes);
    const $ = id => document.getElementById(id);
    const csrf = @json(csrf_token());
    const stages = [{ name: 'Easy', multiplier: 1 }, { name: 'Moderate', multiplier: 2 }, { name: 'Difficult', multiplier: 3 }];
    const initialRuns = @json($initialRuns);
    let starPeriod = @json($starPeriod);
    let memoryPeriod = @json($memoryPeriod);
    let triviaPeriod = @json($triviaPeriod);
    let rankingPeriod = triviaPeriod;
    const canPlay = @json($canPlay);
    const todayRuns = @json($todayRuns);
    const answeredTriviaThemeIds = new Set(@json($answeredTriviaThemeIds));
    const initialTriviaAggregateRankings = @json($triviaAggregateRankings);
    function renderRankings(element, rankings, scoreKey = 'score') { const target = $(element); target.innerHTML = rankings.length ? rankings.map((item, index) => `<tr><td><span class="badge ${index < 3 ? 'badge-gold' : 'badge-soft'} rounded-pill">#${index + 1}</span></td><td class="fw-semibold">${item.name}</td><td>${Number(item[scoreKey] ?? item.total_score ?? 0)} pts</td><td class="text-secondary">${item.played_at ?? ''}</td></tr>`).join('') : '<tr><td colspan="4" class="text-secondary">No scores yet.</td></tr>'; }
    renderRankings('starRankings', initialRuns.filter(run => run.game === 'catch-star'));
    renderRankings('memoryRankings', initialRuns.filter(run => run.game === 'memory-test'));
    function renderTriviaTable(table, rankings) { if (table) table.innerHTML = rankings.length ? rankings.map(item => `<tr><td class="ps-4"><span class="badge ${item.rank <= 3 ? 'badge-gold' : 'badge-soft'} rounded-pill">#${item.rank}</span></td><td class="fw-semibold">${item.name}</td><td>${item.points} / ${item.perfect_score}</td><td class="pe-4 text-secondary">${item.answered_at}</td></tr>`).join('') : '<tr><td colspan="4" class="p-3 text-secondary">No answers were submitted.</td></tr>'; }
    function refreshRankings() { fetch(`/games-rankings?period=${rankingPeriod}`, { headers: { 'Accept': 'application/json' } }).then(response => response.ok ? response.json() : null).then(data => { if (!data) return; renderRankings('starRankings', data.arcade['catch-star']); renderRankings('memoryRankings', data.arcade['memory-test']); Object.entries(data.trivia).forEach(([themeId, rankings]) => renderTriviaTable($(themeId === 'aggregate' ? 'triviaAggregateRankings' : `triviaRankings${themeId}`), rankings)); }).catch(() => {}); }
    function periodSelect(id, value, label, onChange) { const select = document.createElement('select'); select.id = id; select.className = 'form-select form-select-sm ranking-period-select'; select.setAttribute('aria-label', label); select.innerHTML = '<option value="today">Today</option><option value="weekly">Weekly</option><option value="monthly">Monthly</option><option value="all">All time</option>'; select.value = value; select.onchange = () => { onChange(select.value); refreshAllRankings(); }; return select; }
    const starHeading = [...document.querySelectorAll('.card h6')].find(item => item.textContent.trim() === 'Catch the Star');
    const memoryHeading = [...document.querySelectorAll('.card h6')].find(item => item.textContent.trim() === 'Memory Test');
    const triviaPeriodSelect = $('rankingPeriod');
    if (starHeading) starHeading.parentElement.insertBefore(periodSelect('starPeriod', starPeriod, 'Catch the Star ranking period', value => starPeriod = value), starHeading.nextSibling);
    if (memoryHeading) memoryHeading.parentElement.insertBefore(periodSelect('memoryPeriod', memoryPeriod, 'Memory Test ranking period', value => memoryPeriod = value), memoryHeading.nextSibling);
    if (triviaPeriodSelect) { triviaPeriodSelect.value = triviaPeriod; triviaPeriodSelect.onchange = event => { event.preventDefault(); triviaPeriod = triviaPeriodSelect.value; rankingPeriod = triviaPeriod; refreshAllRankings(); }; }
    function renderTriviaRankings(themeId, rankings) { const table = $(`triviaRankings${themeId}`); if (!table) return; table.innerHTML = rankings.length ? rankings.map((item, index) => `<tr class="${index >= 20 ? 'd-none trivia-overflow' : ''}"><td class="ps-4"><span class="badge ${item.rank <= 3 ? 'badge-gold' : 'badge-soft'} rounded-pill">#${item.rank}</span></td><td class="fw-semibold">${item.name}</td><td>${item.points} / ${item.perfect_score}</td><td class="pe-4 text-secondary">${item.answered_at}</td></tr>`).join('') : '<tr><td colspan="4" class="p-3 text-secondary">No answers were submitted.</td></tr>'; const oldButton = $(`triviaMore${themeId}`); if (oldButton) oldButton.remove(); if (rankings.length > 20) { const button = document.createElement('button'); button.id = `triviaMore${themeId}`; button.type = 'button'; button.className = 'btn btn-outline-secondary btn-sm mb-3 ms-3'; button.textContent = 'See more'; button.onclick = () => { table.querySelectorAll('.trivia-overflow').forEach(row => row.classList.toggle('d-none')); button.textContent = button.textContent === 'See more' ? 'Show less' : 'See more'; }; table.parentElement.after(button); } }
    function refreshAllRankings() { fetch(`/games-rankings?star_period=${starPeriod}&memory_period=${memoryPeriod}&trivia_period=${triviaPeriod}`, { headers: { 'Accept': 'application/json' } }).then(response => response.ok ? response.json() : null).then(data => { if (!data) return; renderRankings('starRankings', data.arcade['catch-star']); renderRankings('memoryRankings', data.arcade['memory-test']); Object.entries(data.trivia).forEach(([themeId, rankings]) => themeId === 'aggregate' ? renderTriviaTable($('triviaAggregateRankings'), rankings) : renderTriviaRankings(themeId, rankings)); }).catch(() => {}); }
    setTimeout(() => { document.querySelectorAll('[id^="triviaRankings"]').forEach(table => { const themeId = table.id.replace('triviaRankings', ''); const theme = themes[Number(themeId)]; renderTriviaRankings(themeId, [...table.querySelectorAll('tr')].filter(row => row.querySelector('.badge')).map(row => ({ rank: row.querySelector('.badge')?.textContent?.replace('#', ''), name: row.children[1]?.textContent, points: row.children[2]?.textContent, perfect_score: Math.max(1, Math.floor(Number(theme?.base_points || 0) / (theme?.questions?.length || 1))) * (theme?.questions?.length || 1), answered_at: row.children[3]?.textContent }))); }); renderTriviaTable($('triviaAggregateRankings'), initialTriviaAggregateRankings); }, 0);
    setInterval(refreshAllRankings, 10000);
    const triviaDraft = [];
    if ($('addTriviaQuestion')) {
        $('addTriviaQuestion').onclick = () => {
            const question = $('builderQuestion').value.trim();
            const choices = [$('builderCorrect').value.trim(), $('builderWrongOne').value.trim(), $('builderWrongTwo').value.trim()];
            if (!question || choices.some(choice => !choice)) return alert('Complete the question and all three choices first.');
            triviaDraft.push({ question, answer: choices[0], choices });
            $('questionsJson').value = JSON.stringify(triviaDraft);
            $('questionList').innerHTML = triviaDraft.map((item, index) => `<div class="border rounded-2 p-2 mt-2"><strong>Question ${index + 1}:</strong> ${item.question}</div>`).join('');
            $('builderQuestion').value = '';
            $('builderCorrect').value = '';
            $('builderWrongOne').value = '';
            $('builderWrongTwo').value = '';
            $('builderQuestion').focus();
        };
        $('triviaThemeForm').onsubmit = event => {
            if (triviaDraft.length < 1) {
                event.preventDefault();
                alert('Add at least one question before publishing the theme.');
            }
        };
    }
    async function submitRun(game, scores, period) { const parameter = game === 'catch-star' ? 'star_period' : 'memory_period'; const response = await fetch(`/games/${game}/finish?${parameter}=${period}`, { method: 'POST', headers: {'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body: JSON.stringify({stage_scores:scores}) }); return response.ok ? response.json() : null; }
    function summary(scores, total, message) { message.textContent = `Complete! Stage scores: ${scores.join(' + ')}. Final Score: ${total} points.`; message.classList.remove('d-none'); }

    let starTimer, starMoveTimer, starStage = 0, starScores = [], starScore = 0, starSeconds = 20;
    $('starStart').onclick = () => { starStage = 0; starScores = []; $('starStart').disabled = true; runStarStage(); };
    function runStarStage() { const stage = stages[starStage]; starScore = 0; starSeconds = 20; $('starStage').textContent = `${stage.name} · x${stage.multiplier}`; $('starScore').textContent = 0; $('starTime').textContent = 20; $('starMessage').classList.add('d-none'); $('star').classList.remove('d-none'); moveStar(); clearInterval(starTimer); clearInterval(starMoveTimer); starMoveTimer = setInterval(moveStar, [900, 600, 450][starStage]); starTimer = setInterval(() => { $('starTime').textContent = --starSeconds; if (starSeconds <= 0) { clearInterval(starTimer); clearInterval(starMoveTimer); starScores.push(starScore); $('star').classList.add('d-none'); starStage++; if (starStage < 3) setTimeout(runStarStage, 400); else submitRun('catch-star', starScores, starPeriod).then(result => { const total = result?.total_score ?? (starScores[0] + starScores[1] * 2 + starScores[2] * 3); summary(starScores, total, $('starMessage')); $('starFinalScore').textContent = `Final Score: ${total} points`; renderRankings('starRankings', result?.rankings ?? []); $('starStart').disabled = true; $('starStart').innerHTML = '<i class="bi bi-check-circle me-1"></i>Played today'; }); } }, 1000); }
    function moveStar() { const board = $('starBoard'); $('star').style.left = `${Math.random() * Math.max(0, board.clientWidth - 48)}px`; $('star').style.top = `${Math.random() * Math.max(0, board.clientHeight - 48)}px`; }
    $('star').onclick = () => { starScore++; $('starScore').textContent = starScore; moveStar(); };

    let memoryValues, firstCard, lockBoard, memoryTurns, memoryStage = 0, memoryScores = [], memoryPairsFound = 0, memoryTimer, memorySeconds, memoryEnded;
    $('memoryStart').onclick = () => { memoryStage = 0; memoryScores = []; $('memoryStart').disabled = true; runMemoryStage(); };
    function runMemoryStage() { const icons = ['heart-fill','house-heart-fill','tree-fill','sun-fill','balloon-fill','gift-fill','bell-fill','star-fill','moon-fill','cloud-fill','gem','emoji-smile']; const pairCount = 3 + memoryStage * 2; memoryValues = [...icons.slice(0, pairCount), ...icons.slice(0, pairCount)].sort(() => Math.random() - .5); firstCard = null; lockBoard = false; memoryEnded = false; memoryTurns = 0; memoryPairsFound = 0; memorySeconds = 20; $('memoryStage').textContent = `${stages[memoryStage].name} · x${stages[memoryStage].multiplier} · ${pairCount * 2} tiles`; $('memoryTime').textContent = 20; $('memoryTurns').textContent = 0; $('memoryPairs').textContent = 0; $('memoryBoard').innerHTML = memoryValues.map(icon => `<button class="memory-card" data-icon="${icon}"><i class="bi bi-question-lg"></i></button>`).join(''); document.querySelectorAll('.memory-card').forEach(card => card.onclick = () => flipCard(card)); clearInterval(memoryTimer); memoryTimer = setInterval(() => { $('memoryTime').textContent = --memorySeconds; if (memorySeconds <= 0) finishMemoryStage(); }, 1000); }
    function finishMemoryStage() { if (memoryEnded) return; memoryEnded = true; clearInterval(memoryTimer); memoryScores.push(Math.max(0, memoryValues.length * 2 - memoryTurns)); memoryStage++; if (memoryStage < 3) setTimeout(runMemoryStage, 500); else submitRun('memory-test', memoryScores, memoryPeriod).then(result => { const total = result?.total_score ?? (memoryScores[0] + memoryScores[1] * 2 + memoryScores[2] * 3); summary(memoryScores, total, $('memoryBoard')); $('memoryFinalScore').textContent = `Final Score: ${total} points`; renderRankings('memoryRankings', result?.rankings ?? []); $('memoryStart').disabled = true; $('memoryStart').innerHTML = '<i class="bi bi-check-circle me-1"></i>Played today'; }); }
    function flipCard(card) { if (memoryEnded || lockBoard || card.classList.contains('revealed') || card.classList.contains('matched')) return; card.classList.add('revealed'); card.innerHTML = `<i class="bi bi-${card.dataset.icon}"></i>`; if (!firstCard) { firstCard = card; return; } memoryTurns++; $('memoryTurns').textContent = memoryTurns; if (firstCard.dataset.icon === card.dataset.icon) { firstCard.classList.add('matched'); card.classList.add('matched'); memoryPairsFound++; $('memoryPairs').textContent = memoryPairsFound; firstCard = null; if (memoryPairsFound === memoryValues.length / 2) finishMemoryStage(); } else { lockBoard = true; setTimeout(() => { firstCard.classList.remove('revealed'); card.classList.remove('revealed'); firstCard.innerHTML = card.innerHTML = '<i class="bi bi-question-lg"></i>'; firstCard = null; lockBoard = false; }, 700); } }
    $('memoryStage').textContent = 'Ready · x1 · Start when ready';
    $('memoryBoard').innerHTML = '<div class="text-secondary small text-center py-4">Press Start 3 stages to begin.</div>';

    @if ($themes->isNotEmpty())
    let triviaQuestions, triviaIndex, triviaPoints;
    $('triviaStart').disabled = !canPlay;
    $('triviaStart').onclick = startTrivia;
    function selectedTriviaThemeId() { return Number($('themeSelect').value); }
    function selectedTriviaTheme() { return themes[selectedTriviaThemeId()]; }
    function triviaIsOpen() { const dueAt = selectedTriviaTheme()?.due_at; return !dueAt || new Date(dueAt) > new Date(); }
    document.querySelectorAll('#themeSelect option').forEach(option => { const theme = themes[Number(option.value)]; if (theme?.due_at && new Date(theme.due_at) <= new Date()) { option.disabled = true; option.textContent += ' · Closed'; } });
    function updateTriviaStart() { const answered = answeredTriviaThemeIds.has(selectedTriviaThemeId()); const open = triviaIsOpen(); $('triviaStart').disabled = !canPlay || answered || !open; $('triviaStart').innerHTML = answered ? '<i class="bi bi-check-circle me-1"></i>Already answered' : (open ? '<i class="bi bi-play-fill me-1"></i>Start' : '<i class="bi bi-lock-fill me-1"></i>Closed'); }
    $('themeSelect').onchange = updateTriviaStart;
    function startTrivia() { if (!canPlay || answeredTriviaThemeIds.has(selectedTriviaThemeId()) || !triviaIsOpen()) return; triviaQuestions = [...selectedTriviaTheme().questions].sort(() => Math.random() - .5); triviaIndex = 0; triviaPoints = 0; showTrivia(); }
    function showTrivia() { const current = triviaQuestions[triviaIndex]; $('triviaQuestion').textContent = current.question; $('triviaScore').textContent = `Question ${triviaIndex + 1}/${triviaQuestions.length} · ${triviaPoints} points`; $('triviaChoices').innerHTML = [...current.choices].sort(() => Math.random() - .5).map((choice, index) => `<div class="trivia-answer-box"><div class="trivia-answer-label">Answer ${String.fromCharCode(65 + index)}</div><button class="btn btn-outline-secondary text-start trivia-choice" data-answer="${choice.replace(/"/g, '&quot;')}">${choice}</button></div>`).join(''); document.querySelectorAll('.trivia-choice').forEach(button => button.onclick = async () => { document.querySelectorAll('.trivia-choice').forEach(item => item.disabled = true); const response = await fetch(`/games/themes/${$('themeSelect').value}/answer?period=${rankingPeriod}`, {method:'POST',headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},body:JSON.stringify({question_index:current.index,answer:button.dataset.answer})}); if (!response.ok) { button.classList.replace('btn-outline-secondary','btn-danger'); return; } const result = await response.json(); triviaPoints += Number(result.points ?? 0); answeredTriviaThemeIds.add(selectedTriviaThemeId()); updateTriviaStart(); button.classList.replace('btn-outline-secondary','btn-success'); button.textContent += ` · +${result.points} points`; triviaIndex++; setTimeout(() => triviaIndex < triviaQuestions.length ? showTrivia() : finishTrivia(), 500); }); }
    function finishTrivia() { const perfectScore = triviaQuestions.length * Math.max(1, Number(themes[selectedTriviaThemeId()].base_points || 0)); $('triviaQuestion').textContent = `Finished! You scored ${triviaPoints} / ${perfectScore}.`; $('triviaChoices').innerHTML = ''; $('triviaScore').textContent = `Score ${triviaPoints} / ${perfectScore}`; updateTriviaStart(); }
    updateTriviaStart();
    startTrivia();
    @endif
})();
</script>
@endpush
