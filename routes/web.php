<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\EventRaffleController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\BadgeController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\LeaderboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PrizeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RsvpController;
use App\Http\Controllers\SpinController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->middleware('auth')->name('home');

/*
|--------------------------------------------------------------------------
| Guest
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
    Route::get('/forgot-password', [PasswordResetController::class, 'requestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'resetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.reset.update');

    // Registration is handled by RegisteredUserController — it knows about
    // first/middle/last name, gender, birthdate, address and head-of-family.
    Route::get('/register',  [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->name('register.store');
});

/*
|--------------------------------------------------------------------------
| Authenticated — every resident
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/account',     [AccountController::class, 'index'])->name('account');
    Route::post('/account/substitute', [AccountController::class, 'assignSubstitute'])->name('account.substitute');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/surveys', [SurveyController::class, 'index'])->name('surveys.index');
    Route::get('/surveys/{survey}', [SurveyController::class, 'show'])->name('surveys.show');
    Route::post('/surveys/{survey}/respond', [SurveyController::class, 'respond'])->name('surveys.respond');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->withoutMiddleware('auth')->name('leaderboard');
    Route::get('/badges',      [BadgeController::class, 'catalog'])->name('badges.catalog');
    Route::get('/games',       [GameController::class, 'index'])->withoutMiddleware('auth')->name('games.index');
    Route::post('/games/themes', [GameController::class, 'storeTheme'])->middleware('official')->name('games.themes.store');
    Route::delete('/games/themes/{triviaTheme}', [GameController::class, 'destroyTheme'])->middleware('official')->name('games.themes.destroy');
    Route::post('/games/themes/{triviaTheme}/answer', [GameController::class, 'answerTrivia'])->name('games.trivia.answer');
    Route::post('/games/{game}/finish', [GameController::class, 'finishArcade'])->name('games.arcade.finish');
    Route::get('/games/{game}/rankings', [GameController::class, 'rankings'])->name('games.arcade.rankings');
    Route::get('/games-rankings', [GameController::class, 'allRankings'])->withoutMiddleware('auth')->name('games.rankings');

    /* ---------------- Resident ID ---------------- */
    Route::get('/my-id',    [IdCardController::class, 'show'])->name('id-card.show');
    Route::get('/my-id/qr', [IdCardController::class, 'qr'])->name('id-card.qr');

    /* ---------------- Profile ----------------
     | /account is the profile screen, so /profile just redirects there.
     */
    Route::get('/profile',          [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',        [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo',   [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    /* ---------------- Attendance scanning ---------------- */
    Route::get('/attendance',              [AttendanceController::class, 'scanner'])->name('attendance.scanner');
    Route::get('/attendance/scan/{token}', [AttendanceController::class, 'scanner'])->name('attendance.scan');
    Route::post('/attendance/check',       [AttendanceController::class, 'check'])->name('attendance.check');
    Route::middleware('verified')->group(function () {
        Route::get('/attendance/history',      [AttendanceController::class, 'history'])->name('attendance.history');
        Route::post('/attendance/check-by-id', [AttendanceController::class, 'checkById'])->name('attendance.check-by-id');
    });

    /* ---------------- Announcements / events ----------------
     | IMPORTANT: the literal /announcements route MUST be declared before
     | /announcements/{announcement}, otherwise Laravel treats the word
     | "announcements" as a model key and 404s.
     */
    Route::get('/announcements', [AnnouncementController::class, 'index'])->name('announcements');
    Route::get('/announcements/{announcement}',            [AnnouncementController::class, 'show'])->name('announcements.show');
    Route::get('/announcements/{announcement}/scan',       [AttendanceController::class, 'scanner'])->name('announcements.scan');
    Route::get('/announcements/{announcement}/statistics', [AnnouncementController::class, 'statisticsPage'])->name('announcements.statistics');
    Route::get('/announcements/{announcement}/raffle/live', [EventRaffleController::class, 'live'])->name('announcements.raffle.live');
    Route::get('/announcements/{announcement}/raffle/state', [EventRaffleController::class, 'state'])->name('announcements.raffle.state');
    Route::post('/announcements/{announcement}/rsvp',      [RsvpController::class, 'store'])->name('announcements.rsvp');

    /*
    |----------------------------------------------------------------------
    | Officials only — the 'official' alias maps to EnsureOfficial
    | (registered in bootstrap/app.php)
    |----------------------------------------------------------------------
    */
    Route::middleware('official')->group(function () {

        /* Prizes */
        Route::get('/prizes',             [PrizeController::class, 'index'])->name('prizes.index');
        Route::post('/prizes',            [PrizeController::class, 'store'])->name('prizes.store');
        Route::put('/prizes/{prize}',     [PrizeController::class, 'update'])->name('prizes.update');
        Route::delete('/prizes/{prize}',  [PrizeController::class, 'destroy'])->name('prizes.destroy');

        /* Users */
        Route::get('/users',            [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/create',     [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users',           [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])->name('users.edit');
        Route::get('/manage/announcements/{announcement}/summary.pdf', [AttendanceController::class, 'summaryPdf'])->name('announcements.summary.download');
        Route::get('/manage/announcements/{announcement}/summary/print', [AttendanceController::class, 'printSummary'])->name('announcements.summary.print');
        Route::get('/manage/announcements/{announcement}/attendance-sheet.pdf', [AttendanceController::class, 'attendanceSheetPdf'])->name('announcements.attendance.download');
        Route::get('/manage/announcements/{announcement}/attendance-sheet/print', [AttendanceController::class, 'printAttendanceSheet'])->name('announcements.attendance.print');
        Route::get('/audit-logs',       [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/manage/surveys', [SurveyController::class, 'manage'])->name('surveys.manage');
        Route::post('/manage/surveys', [SurveyController::class, 'store'])->name('surveys.store');
        Route::get('/manage/surveys/{survey}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
        Route::put('/manage/surveys/{survey}', [SurveyController::class, 'update'])->name('surveys.update');
        Route::delete('/manage/surveys/{survey}', [SurveyController::class, 'destroy'])->name('surveys.destroy');
        Route::put('/users/{user}',     [UserManagementController::class, 'update'])->name('users.update');
        Route::patch('/users/{user}/verification', [UserManagementController::class, 'toggleVerification'])->name('users.verification.toggle');
        Route::delete('/users/{user}',  [UserManagementController::class, 'destroy'])->name('users.destroy');

        /* Announcement management — declared ONCE only */
        Route::get('/manage/announcements',                         [AnnouncementController::class, 'index'])->name('announcements.index');
        Route::get('/manage/announcements/create',                  [AnnouncementController::class, 'create'])->name('announcements.create');
        Route::post('/manage/announcements',                        [AnnouncementController::class, 'store'])->name('announcements.store');
        Route::get('/manage/announcements/{announcement}/edit',     [AnnouncementController::class, 'edit'])->name('announcements.edit');
        Route::put('/manage/announcements/{announcement}',          [AnnouncementController::class, 'update'])->name('announcements.update');
        Route::delete('/manage/announcements/{announcement}',       [AnnouncementController::class, 'destroy'])->name('announcements.destroy');
        Route::get('/manage/announcements/{announcement}/qr',       [AnnouncementController::class, 'qr'])->name('announcements.qr');
        Route::post('/manage/announcements/{announcement}/participation', [AttendanceController::class, 'recordParticipation'])->name('announcements.participation');
        Route::patch('/manage/announcements/{announcement}/extend', [AnnouncementController::class, 'extendSurvey'])->name('announcements.extend');
        Route::post('/manage/announcements/{announcement}/remind',  [AnnouncementController::class, 'remind'])->name('announcements.remind');
        Route::post('/manage/announcements/{announcement}/raffle/prizes', [EventRaffleController::class, 'storePrize'])->name('announcements.raffle.prizes.store');
        Route::put('/manage/announcements/{announcement}/raffle/prizes/{prize}', [EventRaffleController::class, 'updatePrize'])->name('announcements.raffle.prizes.update');
        Route::delete('/manage/announcements/{announcement}/raffle/prizes/{prize}', [EventRaffleController::class, 'destroyPrize'])->name('announcements.raffle.prizes.destroy');
        Route::post('/manage/announcements/{announcement}/raffle/prizes/{prize}/draw', [EventRaffleController::class, 'draw'])->name('announcements.raffle.draw');

        /* Badges */
        Route::get('/manage/badges',            [BadgeController::class, 'index'])->name('badges.index');
        Route::post('/manage/badges',           [BadgeController::class, 'store'])->name('badges.store');
        Route::put('/manage/badges/{badge}',    [BadgeController::class, 'update'])->name('badges.update');
        Route::delete('/manage/badges/{badge}', [BadgeController::class, 'destroy'])->name('badges.destroy');

        /* Home background */
        Route::get('/manage/appearance',    [AppearanceController::class, 'edit'])->name('appearance.edit');
        Route::post('/manage/appearance',   [AppearanceController::class, 'update'])->name('appearance.update');
        Route::delete('/manage/appearance', [AppearanceController::class, 'destroy'])->name('appearance.destroy');
    });

    /* Verified residents can spin once daily; officials issue extra chances. */
    Route::middleware('verified')->group(function () {
        Route::get('/spin',  [SpinController::class, 'index'])->name('spin');
        Route::post('/spin', [SpinController::class, 'spin'])->name('spin.play');
    });
});