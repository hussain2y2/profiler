<?php

use Illuminate\Support\Facades\Route;

// Mail entries...
Route::post('/profiler-api/mail', 'MailController@index');
Route::get('/profiler-api/mail/{profilerEntryId}', 'MailController@show');
Route::get('/profiler-api/mail/{profilerEntryId}/preview', 'MailHtmlController@show');
Route::get('/profiler-api/mail/{profilerEntryId}/download', 'MailEmlController@show');

// Exception entries...
Route::post('/profiler-api/exceptions', 'ExceptionController@index');
Route::get('/profiler-api/exceptions/{profilerEntryId}', 'ExceptionController@show');
Route::put('/profiler-api/exceptions/{profilerEntryId}', 'ExceptionController@update');

// Dump entries...
Route::post('/profiler-api/dumps', 'DumpController@index');

// Log entries...
Route::post('/profiler-api/logs', 'LogController@index');
Route::get('/profiler-api/logs/{profilerEntryId}', 'LogController@show');

// Notifications entries...
Route::post('/profiler-api/notifications', 'NotificationsController@index');
Route::get('/profiler-api/notifications/{profilerEntryId}', 'NotificationsController@show');

// Queue entries...
Route::post('/profiler-api/jobs', 'QueueController@index');
Route::get('/profiler-api/jobs/{profilerEntryId}', 'QueueController@show');

// Events entries...
Route::post('/profiler-api/events', 'EventsController@index');
Route::get('/profiler-api/events/{profilerEntryId}', 'EventsController@show');

// Gates entries...
Route::post('/profiler-api/gates', 'GatesController@index');
Route::get('/profiler-api/gates/{profilerEntryId}', 'GatesController@show');

// Cache entries...
Route::post('/profiler-api/cache', 'CacheController@index');
Route::get('/profiler-api/cache/{profilerEntryId}', 'CacheController@show');

// Queries entries...
Route::post('/profiler-api/queries', 'QueriesController@index');
Route::get('/profiler-api/queries/{profilerEntryId}', 'QueriesController@show');

// Eloquent entries...
Route::post('/profiler-api/models', 'ModelsController@index');
Route::get('/profiler-api/models/{profilerEntryId}', 'ModelsController@show');

// Requests entries...
Route::post('/profiler-api/requests', 'RequestsController@index');
Route::get('/profiler-api/requests/{profilerEntryId}', 'RequestsController@show');

// View entries...
Route::post('/profiler-api/views', 'ViewsController@index');
Route::get('/profiler-api/views/{profilerEntryId}', 'ViewsController@show');

// Artisan Commands entries...
Route::post('/profiler-api/commands', 'CommandsController@index');
Route::get('/profiler-api/commands/{profilerEntryId}', 'CommandsController@show');

// Scheduled Commands entries...
Route::post('/profiler-api/schedule', 'ScheduleController@index');
Route::get('/profiler-api/schedule/{profilerEntryId}', 'ScheduleController@show');

// Redis Commands entries...
Route::post('/profiler-api/redis', 'RedisController@index');
Route::get('/profiler-api/redis/{profilerEntryId}', 'RedisController@show');

// Monitored Tags...
Route::get('/profiler-api/monitored-tags', 'MonitoredTagController@index');
Route::post('/profiler-api/monitored-tags/', 'MonitoredTagController@store');
Route::post('/profiler-api/monitored-tags/delete', 'MonitoredTagController@destroy');

// Toggle Recording...
Route::post('/profiler-api/toggle-recording', 'RecordingController@toggle');

Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('profiler');
