<?php
require 'vendor/autoload.php';

$app = new Slim\App();

$app->get('/', function ($request, $response, $args) {
    $response->write("Welcome to Slim!");
    return $response;
});

$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

// 학식 메뉴 가져오기
$app->post('/menu', function ($request, $response, $args) {
    $plaintext = $request->getBody();
    $json = json_decode($plaintext);

    $date = date('Y-m-d');
    if( isset($json->action->params->sys_date) ) {
        $tempjson = json_decode($json->action->params->sys_date);
        $date = $tempjson->date;
    }

    $return = array(
        'date'=> $date,
        'echo'=> $json,
    );

    $response->write( json_encode($return) );
    return $response;
});

$app->run();