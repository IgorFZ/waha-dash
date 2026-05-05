<?php

use Illuminate\Support\Facades\Route;

Route::get("/", function () {
  return response()->json([
    'message' => 'Welcome to the Waha Dashboard API',
    'version' => '1.0',
  ]);
});