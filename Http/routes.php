<?php

Route::group(['middleware' => ['web', 'auth', 'roles'], 'roles' => ['admin'], 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AiIntegration\Http\Controllers'], function()
{
    Route::post('/ai-integration/ajax-admin', ['uses' => 'AiIntegrationController@ajaxAdmin', 'laroute' => true])->name('aiintegration.ajax_admin');
});
