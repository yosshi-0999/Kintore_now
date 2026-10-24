<?php

use App\Models\Muscle;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('dashboard', fn () => view('dashboard', [
    'muscleIds' => Muscle::pluck('id', 'name'),
]))
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');

    Route::get('mypage', fn () => redirect()->route('users.show', auth()->user()))->name('mypage');
    Volt::route('users', 'users.index')->name('users.index');
    Volt::route('users/{user}', 'users.show')->name('users.show');

    Volt::route('workouts/create', 'workouts.create')->name('workouts.create');

    Volt::route('plans', 'plans.index')->name('plans.index');
    Volt::route('plans/create', 'plans.edit')->name('plans.create');
    Volt::route('plans/{plan}/edit', 'plans.edit')->name('plans.edit');

    Volt::route('permissions', 'permissions.index')->name('permissions.index');
});

require __DIR__.'/auth.php';
