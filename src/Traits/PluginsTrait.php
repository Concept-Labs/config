<?php

namespace Concept\Config\Traits;

trait PluginsTrait
{
    private bool $isRuntimeProcess = false;

    private bool $isProcessed = false;

    public function setRuntimeProcess(bool $isRuntimeProcess): static
    {
        $this->isRuntimeProcess = $isRuntimeProcess;

        return $this;
    }

    public function isRuntimeProcess(): bool
    {
        return $this->isRuntimeProcess;
    }

    /**
     * Process the plugins
     * 
     * @return static
     */
    protected function processPlugins(): static
    {
        return $this;

        /**
         @todo: 
            do not process?
            or process in any way on direct call?

        // if ($this->isProcessed) {
        //     return $this;
        // }
         */
        

        //$this->hydrate(
            $this->processNode();
        //);

        $this->isProcessed = true;

        return $this;
    }

    /**
     * Process the node
     * Apply the plugins to the node
     * 
     * @param string|null $path
     * @param array $node
     * 
     * @return array
     */
    protected function processNode(string $path = ''): void
    {
        $node = &$this->getRef($path);
        if (!is_array($node)) {
                $value = $this->getPluginManager()->process($path, $node, 
                    $this
                );
                return;
        }

        foreach ($node as $key => &$value) {
            $subPath = $path ? $path . '.' . $key : $key;
            //if (is_array($value)) {
                $this->processNode($subPath);
            // } else {
            //     $value = $this->getPluginManager()->process($subPath, $value, 
            //         $this
            //     );
            // }
        }

        //return $data;
    }
    // protected function processNode(
    //     string|null $path,
    //     array &$node
    // ): array
    // {
    //     $data = [];

    //     foreach ($node as $key => $value) {
    //         $subPath = $path ? $path . '.' . $key : $key;
    //         if (is_array($value)) {
    //             $data[$key] = $this->processNode(
    //                 $subPath,
    //                 $value
    //             );
    //         } else {
    //             $data[$key] = $this->getPluginManager()->process(
    //                 $subPath, 
    //                 $value, 
    //                 $this
    //             );
    //         }
    //     }

    //     return $data;
    // }

}