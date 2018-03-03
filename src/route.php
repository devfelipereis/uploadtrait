<?php

Route::get('/storage', ['as' => 'uploaded-file', 'uses' => function() {
	$path = isset($_GET['path']) ? $_GET['path'] : null;
	if (!$path) {
	    abort(404);
	}
	return response()->file( storage_path('app/') . $path);
}]);