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

$router->post('/get_detail_document', 'DetailDocumentController@get_detail');

// Leer QR
$router->get('/verificar_documento/{id}/{tag}', 'CheckQRController@check_qr');

// Obtener las versiones de un documento
$router->post('/document_versions', 'DocumentVersionController@get_versions');

// Cambiar el estado de una versión y agregar en bitácora
$router->post('/change_state', 'DocumentVersionController@change_state');

// Obtener la bitárora de una versión
$router->post('/get_bitacora', 'DocumentVersionController@get_bitacora');

// Descargar archivo adjuntado en el registro de la bitácora
$router->get('/download_attachment/{id}', 'DocumentVersionController@download_attachment');

// Obtener los filtros
$router->post('/get_filters', 'FilterController@get_filters');

// Obtener los docuentos para el modulo de Verificar Documentos
$router->post('/fetch_documents_check', 'CheckController@fetch_documents_check');

// Obtener los documentos listos para su publicación 
$router->post('/fetch_documents_publication', 'PublicationController@fetch_documents');

// Comprobar el envio de mails 
$router->get('/test_mail', 'MailController@test_mail');

// Obtener el menu del usuario
$router->post('/get_menu', 'MenuController@get_menu');

// Obtener las áreas para la asignación de permisos
$router->post('/get_areas', 'PermisosController@get_areas');

// Obtener las opciones del menu para la concesión de permisos
$router->get('/get_menu_options', 'PermisosController@get_options');

// Guardar los permisos
$router->post('/save_permission', 'PermisosController@save_permission');

// Obtener la lista de permisos
$router->get('/get_permissions', 'PermisosController@get_permissions');

// Obtener el detalle para la edición de los permisos
$router->post('/get_permission_detail', 'PermisosController@get_detail');

// Agregar o quitar un permiso
$router->post('/remove_add_permission', 'PermisosController@remove_add_permission');

// Validar que el usuario tiene acceso a una página en especifico 
$router->post('/check_access', 'MenuController@check_access');

// Obtener el acronimo del tipo de documento
$router->post('/get_acronimo_tipo', 'CodigoController@get_acronimo_tipo');

// Obtener el acronimo de la sección 
$router->post('/get_acronimo_seccion', 'CodigoController@get_acronimo_seccion');

// Validar el tipo de documento y si se generar QR
$router->post('/check_qr_document_type', 'DocumentVersionController@check_qr_document_type');

// Descargar el documento en PDF o el Original
$router->get('/download_document/{id}/{option}', 'DocumentVersionController@download_document');

// Obtener el detalle del documento para editar la información principal
$router->post('/get_detail_edit', 'DetailDocumentController@get_detail_edit');

// Actualizar la información principal de un documento ISO 
$router->post('/update_detail_info', 'DetailDocumentController@update_detail_info');

// Validar que el código del documento no exista tanto en documentos en revisión como en los publicados
$router->post('/check_code', 'CodigoController@check_code');

// Obtener las secciones del portal ISO
$router->post('/get_iso_sections', 'DepurationController@get_iso_sections');

// Obtener los documentos publicados de una sección
$router->post('/get_section_documents', 'DepurationController@get_section_documents');

// Eliminar documentos del portal ISO
$router->post('/delete_documents_portal', 'DepurationController@delete_documents_portal');