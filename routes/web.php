<?php

use App\Http\Controllers\Admin\ClubController;
use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WebsiteController as AdminWebsiteController;
use App\Http\Controllers\ClubAdmin\PlayerController as ClubAdminPlayerController;
use App\Http\Controllers\ClubAdmin\TournamentController as ClubAdminTournamentController;
use App\Http\Controllers\ClubAdmin\UserController as ClubAdminUserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Website\WebsiteController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['middleware' => ['lscache:max-age=3600;stale=30;public']], function () {
    Route::get('/refresh', [WebsiteController::class, 'refresh'])->name('refresh');
    Route::get('/', [WebsiteController::class, 'index'])->name('root')->middleware('dynamic_lstags:home');
    Route::get('/weather', [WebsiteController::class, 'weather'])->name('weather');
    Route::get('/contact', [WebsiteController::class, 'contact'])->name('contact');

    // Tagging club routes so they can be invalidated broadly but also specifically
    // Uses the Route parameters directly in the tag name
    Route::get('/result/{club}', [WebsiteController::class, 'clubResult'])
        ->name('result.club')
        ->middleware('dynamic_lstags:club-{club}');

    Route::get('/result/{club_id}/{tournament_id}', [WebsiteController::class, 'loadTournament'])
        ->name('result.tournament')
        ->middleware('dynamic_lstags:tournament-{tournament_id}');

    Route::get('/result/{club}/{tournament}/{date}', [WebsiteController::class, 'tournamentDateResult'])
        ->name('result.tournament.date')
        ->middleware('dynamic_lstags:tournament-{tournament}-date-{date}');
});

Auth::routes(['verify' => true]);
Route::group(['middleware' => 'auth'], function () {
    // Super Admin and Admin Routes
    Route::get('/admin', [HomeController::class, 'index'])->name('admin');
    Route::POST('admin/result/time', [ResultController::class, 'time'])->name('result.time');
    Route::POST('admin/result/double-stamp', [ResultController::class, 'doubleStamp'])->name('result.double_stamp');
    Route::get('admin/result/refresh', [ResultController::class, 'refresh'])->name('admin.refresh');
    Route::patch('admin/result/update', [ResultController::class, 'updateResult'])->name('admin.result.update');
    Route::resource('admin/result', ResultController::class);
    Route::get('admin/result/{result}/edit/{date?}', [ResultController::class, 'edit'])->name('result.edit.date');

    Route::group(['middleware' => 'club', 'as' => 'club_admin.'], function () {
        // All Super Admin Routes
        Route::resource('club/admin/user', ClubAdminUserController::class);
        Route::resource('club/admin/player', ClubAdminPlayerController::class);
        Route::get('club/admin/players/data', [ClubAdminPlayerController::class, 'getPlayers'])->name('players.data');
        Route::resource('club/admin/tournament', ClubAdminTournamentController::class);
    });

    Route::group(['middleware' => 'super'], function () {
        // All Super Admin Routes
        Route::resource('admin/website', AdminWebsiteController::class);
        Route::post('admin/auto-update-time', [AdminWebsiteController::class, 'autoUpdateTime'])->name('website.auto_update_time');
        Route::post('admin/first-winner-last-winner', [AdminWebsiteController::class, 'firstWinnerLastWinnerConditions'])->name('website.first_winner_last_winner');
        Route::resource('admin/user', UserController::class);
        Route::resource('admin/player', PlayerController::class);
        Route::get('admin/players/data', [PlayerController::class, 'getPlayers'])->name('players.data');
        Route::resource('admin/tournament', TournamentController::class);
        Route::resource('admin/club', ClubController::class);
        Route::resource('admin/news', NewsController::class);
        Route::post('admin/tournament/{tournament}/activate', [TournamentController::class, 'activate'])->name('tournament.activate');

    });
});
