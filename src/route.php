<?php

Route::get('/storage', ['as' => 'uploaded-file', 'uses' => function() {
	$path = $_GET['path'];
	if (!$path) {
	    abort(404);
	}
	return response()->file( storage_path('app/') . $path);
}]);