<?php

declare(strict_types=1);

namespace Laminas\Session\Service;

// phpcs:disable WebimpressCodingStandard.PHP.CorrectClassNameCase

use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\Session\Config\ConfigInterface;
use Laminas\Session\Container;
use Laminas\Session\Exception\RuntimeException;
use Laminas\Session\ManagerInterface;
use Laminas\Session\SaveHandler\SaveHandlerInterface;
use Laminas\Session\SessionManager;
use Laminas\Session\Storage\StorageInterface;
use Laminas\Session\Validator\ValidatorInterface;
use Psr\Container\ContainerInterface;

use function array_merge;
use function array_unique;
use function assert;
use function class_exists;
use function get_debug_type;
use function is_array;
use function is_subclass_of;
use function sprintf;

/**
 * @internal
 *
 * @psalm-internal Laminas\Session
 * @psalm-internal LaminasTest\Session
 * @psalm-import-type OptionsArgument from SessionManager
 */
final class SessionManagerFactory implements FactoryInterface
{
    /**
     * Default configuration for manager behavior
     */
    private array $defaultManagerConfig = [
        'enable_default_container_manager' => true,
    ];

    /**
     * Create session manager object.
     *
     * Will consume any combination (or zero) of the following services, when
     * present, to construct the SessionManager instance:
     *
     * - Laminas\Session\Config\ConfigInterface
     * - Laminas\Session\Storage\StorageInterface
     * - Laminas\Session\SaveHandler\SaveHandlerInterface
     * - Laminas\Session\Validator\ValidatorInterface
     *
     * The first two have corresponding factories inside this namespace.
     * {@see SaveHandlerInterface}, however, does not, due to the differences in implementations,
     * and the fact that save handlers will often be written in userland. As such
     * if you wish to attach a save handler to the manager, you will need to
     * write your own factory, and assign it to the service name
     * "Laminas\Session\SaveHandler\SaveHandlerInterface", (or alias that name
     * to your own service).
     *
     * You can configure limited behaviors via the "session_manager" key of the
     * Config service. Currently, these include:
     *
     * - enable_default_container_manager: whether to inject the created instance
     *   as the default manager for Container instances. The default value for
     *   this is true; set it to false to disable.
     * - validators: ...
     */
    public function __invoke(
        ContainerInterface $container,
        string $requestedName,
        ?array $options = null
    ): ManagerInterface {
        $environmentFactory = null;
        $config             = null;
        $storage            = null;
        $saveHandler        = null;
        $validators         = [];
        $managerConfig      = $this->defaultManagerConfig;
        $options            = [];

        if ($container->has(ConfigInterface::class)) {
            $config = $container->get(ConfigInterface::class);
            if (! $config instanceof ConfigInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    ConfigInterface::class,
                    ConfigInterface::class,
                    get_debug_type($config)
                ));
            }
        }

        if ($container->has(StorageInterface::class)) {
            $storage = $container->get(StorageInterface::class);
            if (! $storage instanceof StorageInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    StorageInterface::class,
                    StorageInterface::class,
                    get_debug_type($storage)
                ));
            }
        }

        if ($container->has(SaveHandlerInterface::class)) {
            $saveHandler = $container->get(SaveHandlerInterface::class);
            if (! $saveHandler instanceof SaveHandlerInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    SaveHandlerInterface::class,
                    SaveHandlerInterface::class,
                    get_debug_type($saveHandler)
                ));
            }
        }

        // Get session manager configuration, if any, and merge with default configuration
        if ($container->has('config')) {
            $configService = $container->get('config');
            if (
                isset($configService['session_manager'])
                && is_array($configService['session_manager'])
            ) {
                /**
                 * @psalm-var array{
                 *     config: array{
                 *         class: string,
                 *         options: array<string, mixed>
                 *     },
                 *     storage: string,
                 *     validators: array{
                 *         classes: list<class-string<ValidatorInterface>>,
                 *         options: array<string, array<string, mixed>>,
                 *     },
                 *     enable_default_container_manager: bool
                 * } $managerConfig
                 */
                $managerConfig = array_merge($managerConfig, $configService['session_manager']);
            }

            if (isset($managerConfig['validators'])) {
                $validatorsConfig = $managerConfig['validators'];
                assert(is_array($validatorsConfig));
                /** @var list<class-string<ValidatorInterface>> $validators */
                $validators = $validatorsConfig['classes'];
            }

            if (isset($managerConfig['options'])) {
                $options = $managerConfig['options'];
            }
        }

        if ($container->has(EnvironmentFactoryInterface::class)) {
            $environmentFactory = $container->get(EnvironmentFactoryInterface::class);
            if (! $environmentFactory instanceof EnvironmentFactoryInterface) {
                throw new ServiceNotCreatedException(sprintf(
                    'SessionManager requires that the %s service implement %s; received "%s"',
                    EnvironmentFactoryInterface::class,
                    EnvironmentFactoryInterface::class,
                    get_debug_type($environmentFactory)
                ));
            }
        }

        $managerClass = class_exists($requestedName) ? $requestedName : SessionManager::class;
        if (! is_subclass_of($managerClass, ManagerInterface::class)) {
            throw new ServiceNotCreatedException(sprintf(
                'SessionManager requires that the %s service implement %s',
                $managerClass,
                ManagerInterface::class
            ));
        }

        /** @psalm-var OptionsArgument $options */
        if ($options['attach_default_validators'] ?? true) {
            $validators = array_merge(SessionManager::DEFAULT_VALIDATORS, $validators);
        }

        /** @psalm-var list<class-string<ValidatorInterface>> $uniqueValidators */
        $uniqueValidators   = array_unique($validators);
        $validatorInstances = [];
        foreach ($uniqueValidators as $validator) {
            if ($container->has($validator)) {
                $validatorInstance = $container->get($validator);
                if (! $validatorInstance instanceof ValidatorInterface) {
                    throw new RuntimeException(sprintf(
                        'SessionManager requires that the validators implement %s; received "%s"',
                        ValidatorInterface::class,
                        get_debug_type($validatorInstance)
                    ));
                }
                $validatorInstances[] = $validatorInstance;
            }
        }

        $manager = new $managerClass(
            $config,
            $storage,
            $saveHandler,
            $validatorInstances,
            $options,
            $environmentFactory
        );

        // If configuration enables the session manager as the default manager for container
        // instances, do so.
        if (
            isset($managerConfig['enable_default_container_manager'])
            && $managerConfig['enable_default_container_manager']
        ) {
            Container::setDefaultManager($manager);
        }

        return $manager;
    }
}
