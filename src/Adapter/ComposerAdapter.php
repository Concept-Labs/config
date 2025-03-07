<?php
namespace Concept\Config\Adapter;

use Composer\InstalledVersions;
use Concept\Config\Adapter\Composer\PackageConfigInterface;
use Concept\Config\ConfigInterface;
use Concept\Config\PathAccess\PathAccess;
use Concept\Config\PathAccess\PathAccessInterface;
use Concept\Singularity\Config\ConfigNodeInterface;

class ComposerAdapter extends JsonAdapter
{
    //const PATH_BUILT = 'var/.cache/composer/config.php';

    private ?PathAccessInterface $storage = null;
    /**
     * Composer packages cache
     * 
     * @var array<string, PackageConfigInterface>
     */
    private array $packagesCache = [];
    private array $installed = [];
    private array $root = [];

    

    public function __construct(private ConfigInterface $config) {
        parent::__construct($config);
        //$this->root = InstalledVersions::getRootPackage();
        $this->installed = InstalledVersions::getAllRawData()[0]['versions'];
    }

    /**
     * Get storage
     * 
     * @return PathAccessInterface
     */
    protected function getStorage(): PathAccessInterface
    {
        return $this->storage ??= new PathAccess();
    }

    /**
     * {@inheritDoc}
     */
    public function import(mixed $source): array
    {
       
        $package = parent::import($source);
        /**
         @todo: validate the package
         */
        //$name = $package['name'] ?? '';

        //$package = $this->installed[$name];

        $this->mergeNamespaceDependency($package['name'], array_keys($package['autoload']['psr-4'] ?? []) );
        $this->mergePakageDependency($package['name'], array_keys($package['require'] ?? []) );
        $this->includeExtra($package['extra'][ConfigNodeInterface::NODE_COMPOSER_EXTRA] ?? []);
        $this->mergeExtra($package['extra'][ConfigNodeInterface::NODE_COMPOSER_EXTRA] ?? []);

        
        return $this->getStorage()->asArray();
    }

    /**
     * {@inheritDoc}
     */
    public function export(mixed $target): bool
    {
        throw new \InvalidArgumentException(
            "Not supported"
        );
    }

    

    /**
     * Build the namespace dependency
     */
    protected function mergeNamespaceDependency(string $name, array $namespaces)
    {
        foreach ($namespaces as $namespace) {
            $this->getStorage()->mergeTo(
                join(
                    '.',
                    [
                        ConfigNodeInterface::NODE_SINGULARITY,
                        ConfigNodeInterface::NODE_NAMESPACE,
                        $namespace,
                        ConfigNodeInterface::NODE_REQUIRE
                    ]
                ), 
                [
                    $name => [
                        //ConfigNodeInterface::NODE_PRIORITY => static::DEFAULT_PACKAGE_PRIORITY
                    ]
                ]
            );
        }
    }

    protected function mergePakageDependency(string $name, array $requires)
    {
        $packageNodePath = join(
            '.',
            [
                ConfigNodeInterface::NODE_SINGULARITY,
                ConfigNodeInterface::NODE_PACKAGE,
                $name
            ]
        );

        $this->getStorage()->mergeTo(
            $packageNodePath,
            []
        );

        foreach ($requires as $require) {

            // if (!$this->getCompabilityValidator()($require)) {
            //    continue;
            // }

            $this->getStorage()->mergeTo(
                join(
                    '.',
                    [
                        $packageNodePath,
                        ConfigNodeInterface::NODE_REQUIRE,
                        $require
                    ]
                ),
                [
                    //ConfigNodeInterface::NODE_PRIORITY => static::DEFAULT_PACKAGE_PRIORITY
                ]
            );
        }
    }

    protected function mergeExtra(array $extra)
    {
        $extra = $this->prepareExtraForMerge($extra);

        $this->getStorage()->merge(
            $extra
        );
    }

    protected function includeExtra(array $extra)
    {
        $includes = $extra['@include'] ?? [];
        
        $includes = is_array($includes) ? $includes : [$includes];

        foreach ($includes as $include) {
            $this->getStorage()->merge(
                $this->config->getAdapter()->import($include)
            ); 
        }
        
    }

    protected function prepareExtraForMerge(array $extra): array
    {
        foreach ($extra as $key => $value) {
            if (strpos($key, '@') === 0) {
                unset($extra[$key]);
            }
        }

        return $extra;
    }

}