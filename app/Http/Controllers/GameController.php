<?php

namespace App\Http\Controllers;

use App\Models\TriviaTheme;
use App\Models\TriviaAnswer;
use App\Models\GameRun;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\BadgeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $defaultPeriod = $this->rankingPeriod($request->string('period')->toString());
        $starPeriod = $this->rankingPeriod($request->string('star_period')->toString() ?: $defaultPeriod);
        $memoryPeriod = $this->rankingPeriod($request->string('memory_period')->toString() ?: $defaultPeriod);
        $triviaPeriod = $this->rankingPeriod($request->string('trivia_period')->toString() ?: $defaultPeriod);
        $userId = $request->user()?->id;
        $todayRuns = $userId ? GameRun::where('user_id', $userId)->whereDate('played_on', today())->pluck('game') : collect();
        $answeredTriviaThemeIds = $userId
            ? TriviaAnswer::where('user_id', $userId)->distinct()->pluck('trivia_theme_id')
            : collect();
        $themes = TriviaTheme::latest()->get();
        $visibleTriviaThemes = $themes;
        $initialRuns = collect([
            'catch-star' => $starPeriod,
            'memory-test' => $memoryPeriod,
        ])->flatMap(fn (string $period, string $game) => collect($this->arcadeRankings($game, $period))->map(fn (array $ranking) => [...$ranking, 'game' => $game]))->values();

        return view('pages.games', [
            'themes' => $themes,
            'rankings' => $triviaPeriod === 'today'
                ? $visibleTriviaThemes->mapWithKeys(fn (TriviaTheme $theme) => [$theme->id => collect($this->triviaRankings($theme, $triviaPeriod))])
                : collect(),
            'triviaAggregateRankings' => $triviaPeriod === 'today'
                ? collect()
                : collect($this->triviaAggregateRankings($visibleTriviaThemes, $triviaPeriod)),
            'initialRuns' => $initialRuns,
            'todayRuns' => $todayRuns,
            'answeredTriviaThemeIds' => $answeredTriviaThemeIds,
            'starPeriod' => $starPeriod,
            'memoryPeriod' => $memoryPeriod,
            'triviaPeriod' => $triviaPeriod,
            'period' => $triviaPeriod,
        ]);
    }

    public function storeTheme(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:100'],
            'base_points' => ['required', 'integer', 'min:1', 'max:100000'],
            'due_at' => ['required', 'date', 'after:now'],
            'questions_json' => ['required', 'json', 'max:20000'],
        ]);

        $questions = collect(json_decode($data['questions_json'], true))->map(fn (array $item) => [
            'question' => trim($item['question'] ?? ''),
            'answer' => trim($item['answer'] ?? ''),
            'choices' => array_values(array_filter(array_map('trim', $item['choices'] ?? []))),
        ])->filter(fn (array $item) => count($item['choices']) === 3 && $item['question'] !== '' && $item['answer'] !== '')->values()->all();

        if (count($questions) < 1) {
            return back()->withErrors(['questions_json' => 'Add at least one question with one correct and two incorrect choices.'])->withInput();
        }

        $theme = TriviaTheme::create([
            'title' => $data['title'],
            'base_points' => $data['base_points'],
            'due_at' => $data['due_at'],
            'questions' => $questions,
            'created_by' => $request->user()->id,
        ]);

        User::query()->select('id')->chunkById(500, function ($users) use ($theme, $request) {
            $now = now();
            UserNotification::insert($users->map(fn ($user) => [
                'user_id' => $user->id,
                'survey_id' => null,
                'title' => 'New trivia published',
                'body' => "Trivia \"{$theme->title}\" is now available until {$theme->due_at->format('M j, Y g:i A')}.",
                'created_by' => $request->user()->id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        });

        return back()->with('success', 'Trivia theme published.');
    }

    public function destroyTheme(TriviaTheme $triviaTheme): RedirectResponse
    {
        $triviaTheme->delete();

        return back()->with('success', 'Trivia theme removed.');
    }

    public function answerTrivia(Request $request, TriviaTheme $triviaTheme): \Illuminate\Http\JsonResponse
    {
        abort_if($triviaTheme->due_at && $triviaTheme->due_at->isPast(), 410, 'This trivia set is closed. Answers and rankings are now being revealed.');
        $data = $request->validate([
            'question_index' => ['required', 'integer', 'min:0'],
            'answer' => ['required', 'string', 'max:255'],
        ]);
        $question = $triviaTheme->questions[$data['question_index']] ?? abort(422, 'Invalid trivia question.');
        $correct = hash_equals((string) $question['answer'], $data['answer']);

        abort_if(TriviaAnswer::where('trivia_theme_id', $triviaTheme->id)
            ->where('user_id', $request->user()->id)
            ->where('question_index', $data['question_index'])
            ->exists(), 429, 'You have already answered this question.');

        $result = DB::transaction(function () use ($request, $triviaTheme, $data, $correct) {
            $questionPoints = $this->triviaQuestionPoints($triviaTheme);
            $perfectScore = count($triviaTheme->questions) * $questionPoints;
            $answer = TriviaAnswer::create([
                'trivia_theme_id' => $triviaTheme->id,
                'user_id' => $request->user()->id,
                'question_index' => $data['question_index'],
                'is_correct' => $correct,
                'answered_at' => now(),
            ]);

            if (! $correct) {
                return ['points' => 0, 'rank' => null];
            }

            $rank = TriviaAnswer::where('trivia_theme_id', $triviaTheme->id)->where('question_index', $data['question_index'])->where('is_correct', true)->count();
            $points = $questionPoints + $this->triviaRankingBonus($perfectScore, $rank);
            $answer->update(['rank' => $rank, 'points_awarded' => $points]);
            $request->user()->increment('points', $points);

            return ['points' => $points, 'rank' => $rank];
        });

        return response()->json([
            'accepted' => true,
            'correct' => $correct,
            'points' => $result['points'],
            'rank' => $result['rank'],
            'perfect_score' => count($triviaTheme->questions) * $this->triviaQuestionPoints($triviaTheme),
            'rankings' => $this->triviaRankings($triviaTheme, $this->rankingPeriod($request->string('trivia_period')->toString() ?: $request->string('period')->toString())),
        ]);
    }

    public function finishArcade(Request $request, string $game): \Illuminate\Http\JsonResponse
    {
        abort_unless(in_array($game, ['catch-star', 'memory-test'], true), 404);
        abort_if(GameRun::where('user_id', $request->user()->id)->where('game', $game)->whereDate('played_on', today())->exists(), 429, 'This game has already been completed today.');
        $data = $request->validate(['stage_scores' => ['required', 'array', 'size:3'], 'stage_scores.*' => ['integer', 'min:0', 'max:100000']]);
        $scores = array_values($data['stage_scores']);
        $total = $scores[0] + ($scores[1] * 2) + ($scores[2] * 3);
        $run = GameRun::create(['user_id' => $request->user()->id, 'game' => $game, 'played_on' => today(), 'stage_scores' => $scores, 'total_score' => $total]);
        $request->user()->increment('points', $run->total_score);
        BadgeService::awardGameBadges($request->user());

        return response()->json([
            'stage_scores' => $scores,
            'total_score' => $run->total_score,
            'rankings' => $this->arcadeRankings($game, $this->rankingPeriod($request->string($game === 'catch-star' ? 'star_period' : 'memory_period')->toString() ?: $request->string('period')->toString())),
        ]);
    }

    public function rankings(string $game): \Illuminate\Http\JsonResponse
    {
        abort_unless(in_array($game, ['catch-star', 'memory-test'], true), 404);

        return response()->json(['rankings' => $this->arcadeRankings($game, $this->rankingPeriod(request()->string('period')->toString()))]);
    }

    public function allRankings(Request $request): \Illuminate\Http\JsonResponse
    {
        $defaultPeriod = $this->rankingPeriod($request->string('period')->toString());
        $starPeriod = $this->rankingPeriod($request->string('star_period')->toString() ?: $defaultPeriod);
        $memoryPeriod = $this->rankingPeriod($request->string('memory_period')->toString() ?: $defaultPeriod);
        $triviaPeriod = $this->rankingPeriod($request->string('trivia_period')->toString() ?: $defaultPeriod);
        $themes = TriviaTheme::latest()->get();
        $visibleTriviaThemes = $themes;

        return response()->json([
            'arcade' => [
                'catch-star' => $this->arcadeRankings('catch-star', $starPeriod),
                'memory-test' => $this->arcadeRankings('memory-test', $memoryPeriod),
            ],
            'trivia' => $triviaPeriod === 'today'
                ? $visibleTriviaThemes->mapWithKeys(fn (TriviaTheme $theme) => [$theme->id => $this->triviaRankings($theme, $triviaPeriod)])
                : ['aggregate' => $this->triviaAggregateRankings($visibleTriviaThemes, $triviaPeriod)],
        ]);
    }

    private function triviaRankings(TriviaTheme $theme, string $period = 'all'): array
    {
        return TriviaAnswer::with(['user', 'theme'])
            ->where('trivia_theme_id', $theme->id)
            ->when($this->periodStart($period), fn ($query, Carbon $start) => $query->where('answered_at', '>=', $start))
            ->get()
            ->groupBy('user_id')
            ->map(fn ($answers) => [
                'name' => $answers->first()->user->name,
                'points' => (int) $answers->sum('points_awarded'),
                'perfect_score' => count($theme->questions) * $this->triviaQuestionPoints($theme),
                'answered_at' => $answers->max('answered_at')->format('M j, Y g:i A'),
            ])
            ->sortByDesc('points')
            ->values()
            ->map(fn (array $ranking, int $index) => [...$ranking, 'rank' => $index + 1])
            ->all();
    }

    private function triviaAggregateRankings($themes, string $period = 'all'): array
    {
        $themeIds = $themes->pluck('id');

        return TriviaAnswer::with(['user', 'theme'])
            ->whereIn('trivia_theme_id', $themeIds)
            ->when($this->periodStart($period), fn ($query, Carbon $start) => $query->where('answered_at', '>=', $start))
            ->get()
            ->groupBy('user_id')
            ->map(function ($answers) {
                $themes = $answers->groupBy('trivia_theme_id');

                return [
                    'name' => $answers->first()->user->name,
                    'points' => (int) $answers->sum('points_awarded'),
                    'perfect_score' => (int) $themes->sum(fn ($themeAnswers) => count($themeAnswers->first()->theme->questions) * $this->triviaQuestionPoints($themeAnswers->first()->theme)),
                    'answered_at' => $answers->max('answered_at')->format('M j, Y g:i A'),
                ];
            })
            ->sortByDesc('points')
            ->take(20)
            ->values()
            ->map(fn (array $ranking, int $index) => [...$ranking, 'rank' => $index + 1])
            ->all();
    }

    private function arcadeRankings(string $game, string $period = 'all'): array
    {
        return GameRun::with('user')->where('game', $game)
            ->when($this->periodStart($period), fn ($query, Carbon $start) => $query->whereDate('played_on', '>=', $start->toDateString()))
            ->get()->groupBy('user_id')->map(fn ($runs) => [
                'name' => $runs->first()->user->name,
                'score' => (int) $runs->sum('total_score'),
                'played_at' => $runs->max('created_at')->format('M j, Y g:i A'),
            ])->sortByDesc('score')->take(20)->values()->map(fn (array $ranking, int $index) => [...$ranking, 'rank' => $index + 1])->all();
    }

    private function triviaQuestionPoints(TriviaTheme $theme): int
    {
        return max(1, (int) $theme->base_points);
    }

    private function triviaRankingBonus(int $perfectScore, int $rank): int
    {
        $percentage = match (true) {
            $rank === 1 => 0.50,
            $rank === 2 => 0.30,
            $rank === 3 => 0.20,
            $rank <= 20 => 0.15,
            default => 0,
        };

        return (int) round($perfectScore * $percentage);
    }

    private function rankingPeriod(?string $period): string
    {
        return in_array($period, ['today', 'weekly', 'monthly', 'all'], true) ? $period : 'all';
    }

    private function periodStart(string $period): ?Carbon
    {
        return match ($period) {
            'today' => now()->startOfDay(),
            'weekly' => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
            default => null,
        };
    }
}
