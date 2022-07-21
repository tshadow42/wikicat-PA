<?php
namespace App;
session_start();
require "conf.inc.php";

use App\Core\ErrorManager;
use App\Controller\WikiPage;

function myAutoloader($class){
    $class = str_ireplace("App\\", "", $class);
    $class = str_ireplace("\\", "/", $class);
    if(file_exists($class.".class.php")){
        include $class.".class.php";
    }
}

spl_autoload_register("App\myAutoloader");

$uri = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "?"));

if(empty($uri)){
    $uri = $_SERVER["REQUEST_URI"];
}

if (preg_match("#^\/w\/((?!\/).)*$#", $uri)){
    $wikiPage = new WikiPage();

    $uri = trim(str_replace('/w/','',$uri));

    $wikiPage->show(strtolower($uri));

} else if (preg_match("#^\/w\/edit\/((?!\/).)*$#", $uri)){
    $wikiPage = new WikiPage();

    $uri = trim(str_replace('/w/edit/','',$uri));

    $wikiPage->edit(strtolower($uri));

} else if (preg_match("#^\/w\/update\/((?!\/).)*$#", $uri)){
    $wikiPage = new WikiPage();

    $uri = trim(str_replace('/w/update/','',$uri));

    $wikiPage->updatePage(strtolower($uri));

} else {
    $routeFile = "routes.yml";
    if(!file_exists($routeFile)){

        die("Le fichier ".$routeFile." n'existe pas");
    }

    $routes = yaml_parse_file($routeFile);

    if( empty($routes[$uri]) || empty($routes[$uri]["controller"])  || empty($routes[$uri]["action"]) ){
        die("La route est introuvable");
    }

    $controller = ucfirst(strtolower($routes[$uri]["controller"]));
    $action = strtolower($routes[$uri]["action"]);

    $controllerFile = "Controller/".$controller.".class.php";
    if(!file_exists($controllerFile)){
        die("Le controller ".$controllerFile." n'existe pas");
    }
    include $controllerFile;

    $controller = "App\\Controller\\".$controller;

    if( !class_exists($controller) ){
        die("La classe ".$controller." n'existe pas");
    }

    $objectController = new $controller();

    if( !method_exists($objectController, $action) ){
        die("La methode ".$action." n'existe pas");
    }

    $objectController->$action();
}
