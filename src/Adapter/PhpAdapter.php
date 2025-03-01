<?php
namespace Concept\Config\Adapter;

use Concept\Config\Exception\InvalidArgumentException;

class PhpFileAdapter extends AbstractAdapter
{
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */ 
    public function import(mixed $source): array
    {
        if (is_string($source) || !is_file($source)) {
            throw new InvalidArgumentException('Invalid config source provided. Source is not a file');
        }

        try {
            $data = require $source;
        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to load file: %s (%s) ",
                    $source,
                    $e->getMessage()
                )
            );
        }

        return $data;// ? $this->getConfig()->hydrate($data) : false;
    }

    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function export(mixed $path): bool
    {
        if (!is_string($path)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Invalid config target provided. Target (%s) is not a file',
                    $path
                )
            );
        }

        $data = $this->getConfig()->asArray();

        try {
            $content = "<?php\n\nreturn ".var_export($data, true).";\n";
            file_put_contents($path, $content);

        } catch (\Throwable $e) {
            throw new InvalidArgumentException(
                sprintf(
                    "Unable to save file: %s (%s) ",
                    $path,
                    $e->getMessage()
                )
            );
        }

        return true;
    }
}