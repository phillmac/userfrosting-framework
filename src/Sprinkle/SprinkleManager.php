<?php

/*
 * UserFrosting Framework (http://www.userfrosting.com)
 *
 * @link      https://github.com/userfrosting/framework
 * @copyright Copyright (c) 2013-2021 Alexander Weissman, Louis Charette, Jordan Mele
 * @license   https://github.com/userfrosting/framework/blob/master/LICENSE.md (MIT License)
 */

namespace UserFrosting\Sprinkle;

use Closure;
use ReflectionClass;
use UserFrosting\Bakery\CommandReceipe;
use UserFrosting\Exceptions\BadInstanceOfException;
use UserFrosting\Exceptions\BakeryClassException;
use UserFrosting\Exceptions\SprinkleClassException;
use UserFrosting\Support\Exception\FileNotFoundException;

/**
 * Sprinkle Manager.
 *
 * Manages a collection of loaded Sprinkles for the application.
 * Returns all the informations about the loaded Sprinkles, defined in each SrpinkleReceipe.
 * This class does not perform any action on the application itself, it only serves informations.
 * All routes, commands or other registration process is handled elsewhere.
 *
 * @property string $mainSprinkle
 */
class SprinkleManager
{
    /**
     * @var SprinkleReceipe[] List of avaialable sprinkles
     */
    protected $sprinkles = [];

    /**
     * Load sprinkles on construction.
     *
     * @param string $mainSprinkle
     */
    public function __construct(protected string $mainSprinkle)
    {
        $this->loadSprinkles();
    }

    /**
     * Generate the list of all loaded sprinkles throught the main sprinkle dependencies.
     */
    public function loadSprinkles(): void
    {
        $this->sprinkles = $this->getDependentSprinkles($this->mainSprinkle);
    }

    /**
     * Return a list for the registered bakery commands, recursilvey.
     *
     * @return CommandReceipe[]
     */
    public function getBakeryCommands(): array
    {
        $commands = [];

        foreach ($this->sprinkles as $sprinkle) {
            $sprinkleCommands = array_map([$this, 'validateCommand'], $sprinkle::getBakeryCommands());
            $commands = array_merge($commands, $sprinkleCommands);
        }

        return $commands;
    }

    /**
     * Returns a list of all routes definition files from all sprinkles.
     *
     * @return Closure[] List of PHP files containing routes definitions.
     */
    public function getRoutesDefinitions(): array
    {
        $routes = [];

        foreach ($this->sprinkles as $sprinkle) {
            foreach ($sprinkle::getRoutes() as $route) {
                $routes[] = $this->validateRouteFile($route);
            }
        }

        return $routes;
    }

    /**
     * Returns a list of all PHP-DI services/container definition files, from all sprinkles.
     *
     * @return string[] List of PHP files containing routes definitions.
     */
    public function getServicesDefinitions(): array
    {
        $containers = [];

        foreach ($this->sprinkles as $sprinkle) {
            foreach ($sprinkle::getServices() as $container) {
                $containers = array_merge($this->validateContainerFile($container), $containers);
            }
        }

        return $containers;
    }

    /**
     * Returns a list of available sprinkles.
     *
     * @return SprinkleReceipe[]
     */
    public function getSprinkles(): array
    {
        return $this->sprinkles;
    }

    /**
     * Return a list for the specified sprinkle and it's dependent, recursilvey.
     *
     * @param string $sprinkle Sprinkle to load, and it's dependent.
     *
     * @return SprinkleReceipe[]
     */
    protected function getDependentSprinkles(string $sprinkle): array
    {
        // Validate class
        if (!$this->validateClassIsSprinkleReceipe($sprinkle)) {
            $e = new SprinkleClassException();

            throw $e;
        }

        // Add top sprinkle to return
        $sprinkles = [$sprinkle];

        // Merge dependent sprinkles
        foreach ($sprinkle::getSprinkles() as $dependent) {
            $sprinkles = array_merge($sprinkles, $this->getDependentSprinkles($dependent));
        }

        // Remove duplicate and return
        return array_unique($sprinkles);
    }

    /**
     * Validate the class implements SprinkleReceipe.
     *
     * @param string $class
     *
     * @return bool True/False if class implements SprinkleReceipe
     */
    protected function validateClassIsSprinkleReceipe(string $class): bool
    {
        if (!class_exists($class)) {
            return false;
        }

        $class = new ReflectionClass($class);
        if ($class->implementsInterface(SprinkleReceipe::class)) {
            return true;
        }

        return false;
    }

    /**
     * Validate command string and returns CommandReceipe instance.
     *
     * @param string $command
     *
     * @return CommandReceipe
     */
    protected function validateCommand(string $command): CommandReceipe
    {
        // Get command instance
        $instance = new $command();

        // Class must be an instance of symfony command
        if (!$instance instanceof CommandReceipe) {
            throw new BakeryClassException('Bakery command `' . $instance::class . '` must be instance of ' . CommandReceipe::class);
        }

        return $instance;
    }

    /**
     * Validate route file exist and return Closure the file contains.
     *
     * @throws Exception
     * @throws FileNotFoundException
     */
    protected function validateRouteFile(string $file): Closure
    {
        // Check file exist
        if (!file_exists($file)) {
            throw new FileNotFoundException($file . ' not found');
        }

        // Include file
        $definition = include $file;

        // Make sure file is a closure
        if (!$definition instanceof \Closure) {
            throw new BadInstanceOfException('Route definition must be a Closure');
        }

        return $definition;
    }

    /**
     * Validate container definition file exist and return it's array.
     *
     * @throws Exception
     * @throws FileNotFoundException
     */
    protected function validateContainerFile(string $file): array
    {
        // Check file exist
        if (!file_exists($file)) {
            throw new FileNotFoundException($file . ' not found');
        }

        // Include file
        $definition = include $file;

        // Make sure file is a closure
        if (!is_array($definition)) {
            throw new BadInstanceOfException('Container definition must be an array');
        }

        return $definition;
    }

    /**
     * Register resource streams for all base sprinkles.
     * For each sprinkle, register its resources and then run its initializer.
     */
    // TODO
    // public function addResources(): void
    // {
    //     foreach ($this->sprinkles as $sprinkleName => $sprinkle) {
    //         $this->addSprinkleResources($sprinkleName);
    //     }
    // }

    /**
     * Register a sprinkle as a locator location.
     *
     * @param string $sprinkleName
     */
    // TODO
    // public function addSprinkleResources(string $sprinkleName): void
    // {
    //     /** @var \UserFrosting\UniformResourceLocator\ResourceLocator $locator */
    //     $locator = $this->ci->locator;
    //     $locator->registerLocation($sprinkleName, $this->getSprinklePath($sprinkleName));
    // }

    /**
     * Returns the sprinkle service provider class.
     *
     * @param string $sprinkleName
     *
     * @return string
     */
    // TODO
    // protected function getSprinkleDefaultServiceProvider(string $sprinkleName): string
    // {
    //     return $this->getSprinkleClassNamespace($sprinkleName) . '\\ServicesProvider\\ServicesProvider';
    // }

    /**
     * Takes the name of a Sprinkle, and creates an instance of the initializer object (if defined).
     *
     * Creates an object of a subclass of UserFrosting\System\Sprinkle\Sprinkle if defined for the sprinkle (converting to StudlyCase).
     * Otherwise, returns null.
     *
     * @param string $sprinkleName The name of the Sprinkle to initialize.
     *
     * @return SprinkleReceipe|null Sprinkle class instance or null if no such class exist
     */
    // public function bootSprinkle(string $sprinkleName): ?Sprinkle
    // {
    //     $fullClassName = $this->getSprinkleClass($sprinkleName);

    //     // Check that class exists.  If not, set to null
    //     if (class_exists($fullClassName)) {
    //         $sprinkle = new $fullClassName($this->ci);

    //         if (!$sprinkle instanceof Sprinkle) {
    //             throw new SprinkleClassException("$fullClassName must be an instance of " . Sprinkle::class);
    //         }

    //         return $sprinkle;
    //     } else {
    //         return null;
    //     }
    // }

    /**
     * Returns a list of available sprinkle names.
     *
     * @return string[]
     */
    // TODO
    // public function getSprinkleNames(): array
    // {
    //     return array_keys($this->sprinkles);
    // }

    /**
     * Return if a Sprinkle is available
     * Can be used by other Sprinkles to test if their dependencies are met.
     *
     * @param string $sprinkleName The name of the Sprinkle
     *
     * @return bool
     */
    // TODO
    // public function isAvailable(string $sprinkleName): bool
    // {
    //     return (bool) $this->getSprinkle($sprinkleName);
    // }

    /**
     * Find sprinkle value from the sprinkles.json.
     *
     * @param string $sprinkleName
     *
     * @return string|false Return sprinkle name or false if sprinkle not found
     */
    // TODO
    // public function getSprinkle(string $sprinkleName)
    // {
    //     $mathches = preg_grep("/^$sprinkleName$/i", $this->getSprinkleNames());

    //     if (count($mathches) <= 0) {
    //         return false;
    //     }

    //     return array_values($mathches)[0];
    // }

    /**
     * Interate through the list of loaded Sprinkles, and invoke their ServiceProvider classes.
     */
    // public function registerAllServices(): void
    // {
    //     foreach ($this->getSprinkleNames() as $sprinkleName) {
    //         $this->registerServices($sprinkleName);
    //     }
    // }

    /**
     * Register services for a specified Sprinkle.
     *
     * @param string $sprinkleName
     */
    // public function registerServices(string $sprinkleName): void
    // {
    //     //Register the default services
    //     $fullClassName = $this->getSprinkleDefaultServiceProvider($sprinkleName);

    //     // Check that class exists, and register services
    //     if (class_exists($fullClassName)) {
    //         // Register core services
    //         $serviceProvider = new $fullClassName();
    //         $serviceProvider->register($this->ci);
    //     }

    //     // Register services from other providers
    //     if ($this->sprinkles[$sprinkleName] instanceof Sprinkle) {
    //         $this->sprinkles[$sprinkleName]->registerServices();
    //     }
    // }
}
