<?php

Route::group([
    'namespace' => 'Denngarr\Seat\SeatSrp\Http\Controllers',
    'middleware' => ['web','auth'],
    'prefix' => 'api/v2/srp/metrics/web'
], function(){

    Route::get('/summary/monthly/{status}/{limit?}', [
        'as' => 'srp.metrics.api.web.summary.monthly',
        'uses' => 'SrpMetricsApiController@getSummaryMonthly',
    ]);

    Route::get('/summary/user/{status}/{group_id?}/{limit?}', [
        'as' => 'srp.metrics.api.web.summary.user',
        'uses' => 'SrpMetricsApiController@getSummaryUser',
    ]);

    Route::get('/top/ship/{status}/{limit?}',[
        'as' => 'srp.metrics.api.web.top.ship',
        'uses' => 'SrpMetricsApiController@getTopShip',
    ]);

    Route::get('/top/user/{status}/{limit?}',[
        'as' => 'srp.metrics.api.web.top.user',
        'uses' => 'SrpMetricsApiController@getTopUser'
    ]);
});

Route::group([
    'namespace' => 'Denngarr\Seat\SeatSrp\Http\Controllers',
    'middleware' => ['api.auth'],
    'prefix' => 'api/v2/srp/metrics'
], function(){
    Route::get('/summary/monthly/{status}/{limit?}', [
        'as' => 'srp.metrics.api.summary.monthly',
        'uses' => 'SrpMetricsApiController@getSummaryMonthly',
    ]);

    Route::get('/summary/user/{status}/{group_id?}/{limit?}', [
        'as' => 'srp.metrics.api.summary.user',
        'uses' => 'SrpMetricsApiController@getSummaryUser',
    ]);

    Route::get('/top/ship/{status}/{limit?}',[
        'as' => 'srp.metrics.api.top.ship',
        'uses' => 'SrpMetricsApiController@getTopShip',
    ]);

    Route::get('/top/user/{status}/{limit?}',[
        'as' => 'srp.metrics.api.top.user',
        'uses' => 'SrpMetricsApiController@getTopUser'
    ]);
});


Route::group([
    'namespace' => 'Denngarr\Seat\SeatSrp\Http\Controllers',
    'prefix' => 'srp'
], function () {

    Route::group([
        'middleware' => ['web', 'auth', 'locale'],
    ], function () {

        Route::get('/', [
            'as'   => 'srp.request',
            'uses' => 'SrpController@srpGetRequests',
            'middleware' => 'can:srp.request'
        ]);

        Route::get('/getkillmail', [
            'as'   => 'srp.getKillMail',
            'uses' => 'SrpController@srpGetKillMail',
            'middleware' => 'can:srp.request'
        ]);

        Route::post('/savekillmail', [
            'as'   => 'srp.saveKillMail',
            'uses' => 'SrpController@srpSaveKillMail',
            'middleware' => 'can:srp.request'
        ]);

        Route::get('/admin', [
            'as'   => 'srpadmin.list',
            'uses' => 'SrpAdminController@srpGetKillMails',
            'middleware' => 'can:srp.admin-readonly'
        ]);

        Route::get('/admin/{kill_id}/{action}', [
            'as'   => 'srpadmin.settle',
            'uses' => 'SrpAdminController@srpApprove',
            'middleware' => 'can:srp.settle'
        ])->where(['action' => 'Approved|Rejected|Paid Out|Pending']);

        Route::get('/insurances/{kill_id}', [
            'as' => 'srp.insurances',
            'uses' => 'SrpController@getInsurances',
            'middleware' => 'can:srp.request',
        ]);

        Route::get('/ping/{kill_id}', [
        	'as' => 'srp.ping',
	        'uses' => 'SrpController@getPing',
	        'middleware' => 'can:srp.request',
        ]);

        Route::get('/export', [
            'as' => 'srp.export-page',
            'uses' => 'SrpController@showExportPage',
            'middleware' => 'can:srp.settle',
        ]);

        Route::get('/export-execl', [
            'as' => 'srp.export-execl',
            'uses' => 'SrpController@exportExecl',
            'middleware' => 'can:srp.settle',
        ]);

        Route::get('/export-execl/download/{path?}', [
            'as' => 'srp.export-execl-download',
            'uses' => 'SrpController@downloadExecl',
            'middleware' => 'can:srp.settle',
        ]);

        Route::get('/mark-paid', [
            'as' => 'srp.mark-paid',
            'uses' => 'SrpController@markPaid',
            'middleware' => 'can:srp.settle',
        ]);

        Route::get('/action-history', [
            'as' => 'srp.action-history',
            'uses' => 'SrpController@getActionHistory',
            'middleware' => 'can:srp.settle',
        ]);

        Route::group([
            'middleware' => 'can:srp.settle',
            'prefix' => 'metrics'
        ], function (){

            Route::get('/{srp_status?}', [
                'as' => 'srp.metrics',
                'uses' => 'SrpMetricsController@getIndex',
            ]);
        });
    });
});
