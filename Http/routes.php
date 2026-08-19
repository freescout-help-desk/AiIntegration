<?php

Route::group(['middleware' => 'web', 'prefix' => \Helper::getSubdirectory(), 'namespace' => 'Modules\AiIntegration\Http\Controllers'], function()
{
    Route::get('/', 'AiIntegrationController@index');
});
