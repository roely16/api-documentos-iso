<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$router->post('/', function () use ($router) {
    return $router->app->version();
});

$router->post('/login', 'LoginController@login');

$router->post('/obtener_firmas', 'QRController@obtener_firmas');

$router->post('/process_pdf', 'QRController@process_pdf');

$router->post('/test_qr', 'QRController@test_qr');

$router->post('/get_documentos_revision', 'UploadDocumentController@get_documents_revision');

$router->post('/get_form_create', 'UploadDocumentController@get_form_create');

$router->post('/upload_document', 'UploadDocumentController@upload_document');