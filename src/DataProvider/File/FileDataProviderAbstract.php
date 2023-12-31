<?php

namespace Cl\Config\DataProvider\File;

use Throwable;
use Exception;

use Cl\Config\DataProvider\ConfigDataProviderInterface;
use Cl\Config\DataProvider\ConfigDataProviderCacheTrait;

use Cl\Config\DataProvider\File\Exception\FileWriteException;
use Cl\Config\DataProvider\File\Exception\FileReadException;
use Cl\Config\DataProvider\File\Exception\InvalidArgumentException;
use Cl\Config\DataProvider\File\FileDataProviderCacheKeyTrait;

/**
 * Configuration provider from JSON file.
 */
abstract class FileDataProviderAbstract extends \SplFileObject implements ConfigDataProviderInterface
{
    // Cache support -----------------
    use ConfigDataProviderCacheTrait;
    use FileDataProviderCacheKeyTrait;
    //--------------------------------

    const F_TIMEOUT = 5; //seconds

    /**
     * {@inheritDoc}
     */
    abstract public function toArray(): array;
    
    /**
     * {@inheritDoc}
     * 
     * @throws InvalidArgumentException
     */
    public function __construct(string $filename, string $mode = "rw+", bool $useIncludePath = false, mixed $context = null)
    {
        try {
            if (!preg_match('/[waAxXcCu]/', $mode) && !file_exists($filename)) {
                throw new Exception("File not exists (used read mode)");
            }

            parent::__construct($filename, $mode, $useIncludePath, $context);

        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                sprintf('Unable to create provider using file "%s": "%s', $filename, $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }
    

    /**
     * Read file content as single string
     * 
     * @param bool $throwOnFail Throws Exception on read failure if this parameter is set to True
     * 
     * @return string|bool File content or False on failure
     * @throws FileReadException 
     */
    public function read(bool $throwOnFail = true): string|bool
    {
        $content = '';
        try {
            $this->rewind();
            
            return match (false) {
                $this->tflock(LOCK_SH) => $throwOnFail ? throw new Exception('Couldn\'t acquire lock within the timeout') : false,
                strlen($content = $this->fread($this->getSize())) => $throwOnFail ? throw new Exception('File is empty') : false,
                $this->flock(LOCK_UN) => $throwOnFail ? throw new Exception('Couldn\'t release within the timeout') : false,
                default => $content,
            };

        } catch(Throwable $e) {
            throw new FileReadException(
                sprintf('Unable to read config from file "%s" : %s', $this->getPathname(), $e->getMessage()),
                $e->getCode(),
                $e
            );
        }
    }

    /**
     * Write content to file
     * 
     * @param string $content     The content to write
     * @param bool   $throwOnFail If True than throw exception if writting was failed or return boolean if parameter set to False
     * 
     * @return bool
     * @throws FileWriteException
     */
    public function write(string $content, bool $throwOnFail = true): bool
    {
        try {
            return match (false) {
                $this->tflock(LOCK_EX | LOCK_NB) => $throwOnFail ? throw new Exception('Couldn\'t acquire lock within the timeout') : false,
                $this->ftruncate(0) => $throwOnFail ? throw new Exception('Couldn\'t truncate file') : false,
                $this->fwrite($content, strlen($content)) => $throwOnFail ? throw new Exception('Couldn\'t write to file') : false,
                $this->flock(LOCK_UN) => $throwOnFail ? throw new Exception('Couldn\'t release lock') : false,
                default => true,
            };

        } catch (Throwable $e) {
            if ($throwOnFail) {
                throw new FileWriteException(
                    sprintf('Unable to write file "%s": %s', $this->getPathname(), $e->getMessage()),
                    $e->getCode(),
                    $e
                );
            }
        } finally {
            // Release the lock in case of an exception
            $this->flock(LOCK_UN);
        }

        return false;
    }


    /**
     * Get the lock within timeout
     *
     * @param integer $lock 
     * 
     * @return boolean
     */
    protected function tflock(int $lock): bool
    {
        $time = time();
        $locked = false;
        // Retry acquiring an exclusive lock for a certain timeout
        while (!$locked && (time() - $time) <= static::F_TIMEOUT) {
            $locked = $this->flock($lock);
            if (!$locked) {
                usleep(100000); // Sleep for 100 milliseconds before retrying
            }
        }
        return $locked;
    }
}