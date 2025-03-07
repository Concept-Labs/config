<?php
namespace Concept\Config\Adapter;

use Composer\InstalledVersions;
use Concept\Config\Adapter\Composer\PackageConfig;
use Concept\Config\Adapter\Composer\PackageConfigInterface;
use Concept\Config\ConfigInterface;
use Concept\Config\PathAccess\PathAccess;
use Concept\Config\PathAccess\PathAccessInterface;
use Traversable;

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

    // public function __construct(private ContextInterface $context)
    // {
    //     $this->root = InstalledVersions::getRootPackage();
    //     $this->installed = InstalledVersions::getAllRawData()[0]['versions'];
    // }

    public function __construct(private ConfigInterface $config) {
        parent::__construct($config);
        //$this->root = InstalledVersions::getRootPackage();
        $this->installed = InstalledVersions::getAllRawData()[0]['versions'];
    }

    /**
     * {@inheritDoc}
     */
    public function import(mixed $source): array
    {
        if (!is_string($source) && preg_match('/composer\.json$/', $source)) {
            throw new \InvalidArgumentException(
                "Invalid source"
            );
        }

        $data = parent::import($source);

        foreach ($this->getPackages() as $package) {
            $this->getStorage()->merge(
                $package->asArray()
            );
        }

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
     * Get loaded composerPackages
     * 
     * @return Traverseable
     */
    protected function getPackages(): Traversable
    {
        foreach ($this->installed as $name => $package) {
            yield 
                $this->createPackageConfig($name);
            
        }
    }

    protected function createPackageConfig(string $name): PackageConfigInterface
    {
        if (isset($this->packagesCache[$name])) {
            return $this->packagesCache[$name];
        }

        $file =
            (
                $this->installed[$name]['install_path'] 
                ?? throw new \InvalidArgumentException('Package not found: ' . $name)
            ) . DIRECTORY_SEPARATOR.'composer.json';

        return $this->packagesCache[$name] =
            (new PackageConfig($file))
                //->withContext($this->getContext())
                ->build();
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

}