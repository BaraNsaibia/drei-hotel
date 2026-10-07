<?php

// Namespace declaration — the Kernel lives at the root of the App namespace
namespace App;

// Import Symfony's MicroKernelTrait — provides automatic configuration loading
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
// Import the base Kernel class from Symfony
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Kernel — the heart of the Symfony application.
 *
 * This class bootstraps the entire application:
 *   - Loads bundles (from config/bundles.php)
 *   - Loads configuration (from config/packages/)
 *   - Loads routes (from config/routes/)
 *   - Registers services (from config/services.yaml)
 *
 * The MicroKernelTrait handles all of this automatically — no manual configuration needed.
 * This is the standard Symfony 6/7 Kernel with no customizations.
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
