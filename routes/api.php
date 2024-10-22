<?php
    use App\Http\Controllers\DietController;
    use Illuminate\Support\Facades\Route;

    Route::post('/calculate', [DietController::class, 'calculate']);
