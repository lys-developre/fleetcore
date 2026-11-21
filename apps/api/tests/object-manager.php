<?php

declare(strict_types=1);

// Used by phpstan-doctrine to introspect the real entity mappings.

use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

new Dotenv()->bootEnv(__DIR__.'/../.env');

// Always introspect the test kernel: it is the one CI and `make lint` warm up.
$kernel = new Kernel('test', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
