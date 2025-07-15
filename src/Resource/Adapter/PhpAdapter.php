<?php
namespace Concept\Config\Resource\Adapter;

use Concept\Config\Resource\AdapterInterface;
use Concept\Config\Resource\Exception\InvalidArgumentException;

class PhpAdapter implements AdapterInterface
{

    /**
     * {@inheritDoc}
     */
   public static function supports(string $uri): bool
   {
       return pathinfo($uri, PATHINFO_EXTENSION) === 'php';
   }

    /**
    * {@inheritDoc}
    */
    public function read(string $uri): array
    {
        $data = require $uri;

        if (!is_array($data)) {
            throw new InvalidArgumentException('Invalid data');
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function write(string $target, array $data): static
    {
        $encoded = $this->encode($data);
        $content = <<<PHP
<?php
return $encoded;
PHP;

        file_put_contents($target, $content);
        

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function decode(string $data): array
    {
        throw new \RuntimeException('Use read method instead');
    }

    /**
     * {@inheritDoc}
     */
    public function encode(array $data): string
    {
        return var_export($data, true);
    }
}