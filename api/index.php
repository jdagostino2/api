<?php
# https://github.com/tutsplus/create-a-rest-api-with-phalcon/blob/master/phalcon-api/app/models/Cars.php
use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;
use Phalcon\Http\Response;

$loader = new Loader();

$loader->registerDirs(
    array(
        __DIR__ . '/models/'
    )
)->register();

$di = new FactoryDefault();

// Set up the database service
$di->set('db', function () {
    return new PdoMysql(
        array(
            "host"     => "localhost",
            "username" => "root",
            "password" => "xxxxxxxx",
            "dbname"   => "tardis"
        )
    );
});

// Create and bind the DI to the application
$app = new Micro($di);

// Default response when hitting just the api URL.
$app->get('/', function () {
	echo "Welcome to the API!\n";
});

// Return all servers in the database.
$app->get('/servers', function () use ($app){

	$phql = "SELECT * FROM tardis.inventorylinux ORDER BY id DESC";
    $servers = $app->modelsManager->executeQuery($phql);

    $data = array();
    foreach ($servers as $server) {
        $data[] = array(
            'id'   			=> 	$server->id,
            'hostname' 		=> 	$server->hostname,
			'ip_addr'		=>	$server->ip_addr,
			'os_release'	=>	$server->os_release
        );
    }

    echo json_encode($data);
});

// Print only the one server based on the ID given. 
$app->get('/servers/{id:[0-9]+}', function ($id) use ($app){

    $phql = "SELECT * FROM inventorylinux where id = :id: ";
    $values = array('id'   =>  $id);
    $server = $app->modelsManager->executeQuery($phql, $values)->getFirst();

	$response = new Response();

	if ($server == false) {
		$response->setJsonContent(
			array(
				'status'	=>	'NOT-FOUND'
			)
		);
	}
	else {
		$response->setJsonContent(
            array(
				'status'	=>	'FOUND',		
    				$data = array(
        				'id'            =>  $server->id,
        				'hostname'      =>  $server->hostname,
        				'ip_addr'       =>  $server->ip_addr,
        				'os_release'    =>  $server->os_release
    				)
			)
		);
	}
	return $response;
});

// Search for a server by IP Address. 
$app->get('/servers/search/{ip_addr}', function ($ip_addr) use ($app){

    $phql = "SELECT * FROM inventorylinux where ip_addr = :ip_addr: ";
	$values = array('ip_addr'	=>	$ip_addr);
    $server = $app->modelsManager->executeQuery($phql, $values)->getFirst();

    $response = new response();

    if ($server == false) {
        $response->setJsonContent(
            array(
                'status'    =>  'NOT-FOUND'
            )
        );
    }
    else {
        $response->setJsonContent(
            array(
                'status'    =>  'FOUND',
					$data = array(
						'id'            =>  $server->id,
						'hostname'      =>  $server->hostname,
						'ip_addr'       =>  $server->ip_addr,
						'os_release'    =>  $server->os_release
					)
			)
		);
	}
	
	return $response;
});

//  Add new servers 
$app->post('/servers', function () use ($app) {

	$phql = "INSERT INTO inventorylinux (hostname, ip_addr, os_release) VALUES (:hostname:, :ip_addr:, :os_release:)";

	$server = $app->request->getJsonRawBody();
	$values = array(
		'hostname'      =>  $server->hostname,
		'ip_addr'       =>  $server->ip_addr,
		'os_release'    =>  $server->os_release
	);

	$results = $app->modelsManager->executeQuery($phql, $values);	

    $response = new Response();

    if ($results->success() == TRUE) {

		$response->setStatusCode(201, "Created");

		$id = $results->getModel()->id;

        $response->setJsonContent(
            array(
                'status'    =>  'OK',
				'data'		=>	$id
            )
        );
    }
    else {
	
		$response->setStatusCode(409, "Conflict");

		$errors = array();
		foreach($results->getMessages() as $message) {
			$errors[] = $message->getMessage();
		}

        $response->setJsonContent(
            array(
                'status'    =>  'ERROR',
				'messages'	=>	$errors
            )
        );
    }
	
	return $response;

});

// Update server based on primary key. 
$app->put('/servers/{id:[0-9]+}', function ($id) use ($app) {

    $phql = "UPDATE inventorylinux SET hostname = :hostname:, ip_addr = :ip_addr:, os_release = :os_release: WHERE id = :id: ";

    $updatedServerValues = $app->request->getJsonRawBody();

    $values = array(    
		'id'			=>	$id,
        'hostname'      =>  $updatedServerValues->hostname,
        'ip_addr'       =>  $updatedServerValues->ip_addr,
        'os_release'    =>  $updatedServerValues->os_release
    );

    $results = $app->modelsManager->executeQuery($phql, $values);

    $response = new Response();

    if ($results->success() == TRUE) {

        $response->setStatusCode(200, "OK");

        $response->setJsonContent(
            array(
                'status'    =>  'OK',
            )
        );
    }
    else {

        $response->setStatusCode(409, "Conflict");

        $errors = array();
        foreach($results->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent(
            array(
                'status'    =>  'ERROR',
                'messages'  =>  $errors
            )
        );
    }

    return $response;

});

$app->delete('/servers/{id:[0-9]+}', function ($id) use ($app) {

    $phql = "DELETE FROM inventorylinux where id = :id:";

    $updatedServerValues = $app->request->getJsonRawBody();

    $values = array(            
        'id'	=>  $id,
    );  

    $results = $app->modelsManager->executeQuery($phql, $values);

    $response = new Response();

    if ($results->success() == TRUE) {

        $response->setStatusCode(200, "OK");

        $response->setJsonContent(
            array(
                'status'    =>  'OK',
            )
        );
    }
    else {

        $response->setStatusCode(409, "Conflict");
    
        $errors = array();
        foreach($results->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent(
            array(
                'status'    =>  'ERROR',
                'messages'  =>  $errors
            )
        );
    }

    return $response;

});

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->sendHeaders();
    echo 'This is crazy, but this page was not found!\n';
});

$app->handle();
