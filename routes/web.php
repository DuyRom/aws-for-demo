<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\ImageController;

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

Route::get('/', function () {
    return view('welcome');
});


// Route::get('buckets', function(){
//     $disk = 'invoices';
//     $heroImage = Storage::get('hero.png');
//     $uploadedPath = Storage::disk($disk)->put('hero.png', $heroImage);
//     return Storage::disk($disk)->url($uploadedPath);
// });

Route::get('image-upload', [ ImageController::class, 'upload' ])->name('image.upload');
Route::post('image-store', [ ImageController::class, 'store' ])->name('image.upload.post');

Route::get('/upload-form', [ImageController::class, 'showForm']);
Route::post('/upload', [ImageController::class, 'uploadWithS3'])->name('file.upload');

Route::get('/get-image/{image}', [ImageController::class, 'getImage'])->name('get.image');
Route::get('/get-all-image', [ImageController::class, 'getAllImages']);
Route::get('/down-load', [ImageController::class, 'downloadFile']);