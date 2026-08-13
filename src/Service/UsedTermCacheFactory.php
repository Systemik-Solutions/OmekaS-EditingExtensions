<?php declare(strict_types=1);

namespace EditingExtensions\Service;

use EditingExtensions\UsedTermCache;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class UsedTermCacheFactory implements FactoryInterface
{
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        ?array $options = null
    ): UsedTermCache {
        return new UsedTermCache(
            $container->get('Omeka\Connection'),
            $container->get('Omeka\Settings')
        );
    }
}
