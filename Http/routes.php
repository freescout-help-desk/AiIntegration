<?php

# Any user
Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['user', 'admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AiIntegration\Http\Controllers'], function()
{
    Route::post('/ai-integration/ajax', ['uses' => 'AiIntegrationController@ajax', 'laroute' => true])->name('aiintegration.ajax');
    Route::get('/ai-integration/ajax_html/{action}', ['uses' => 'AiIntegrationController@ajaxHtml'])->name('aiintegration.ajax_html');
});

# Admin
Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AiIntegration\Http\Controllers'], function()
{
    Route::post('/ai-integration/ajax-admin', ['uses' => 'AiIntegrationController@ajaxAdmin', 'laroute' => true])->name('aiintegration.ajax_admin');
});
