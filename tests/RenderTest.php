<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Kernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Dotenv\Dotenv;

(new Dotenv())->bootEnv(__DIR__ . '/../.env');

$kernel = new Kernel('dev', true);
$kernel->boot();

$urls = [
    '/',
    '/chambres',
    '/chambres/1',
    '/a-propos',
    '/services',
    '/contact',
    '/connexion',
    '/inscription',
];

foreach ($urls as $url) {
    $request = Request::create($url);
    $session = new Session(new MockArraySessionStorage());
    $request->setSession($session);
    $response = $kernel->handle($request);
    $status = $response->getStatusCode();
    echo "URL: $url -> Status: $status\n";
}
