<?php

namespace Eleanorsoft\DiCompile\Module\Di\App\Task\Operation;

use Magento\Framework\App\Bootstrap;
use Magento\Framework\Exception\FileSystemException;
use Magento\Setup\Module\Di\App\Task\OperationInterface;
use Magento\Setup\Module\Di\Code\Reader\ClassesScanner;
use Magento\Setup\Module\Di\Code\Scanner\DirectoryScanner;
use Magento\Setup\Module\Di\Code\Scanner\PhpScanner;

class ApplicationCodeGenerator implements OperationInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var ClassesScanner
     */
    private $classesScanner;

    /**
     * @var PhpScanner
     */
    private $phpScanner;

    /**
     * @var DirectoryScanner
     */
    private $directoryScanner;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @param ClassesScanner $classesScanner
     * @param PhpScanner $phpScanner
     * @param DirectoryScanner $directoryScanner
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param array $data
     */
    public function __construct(
        ClassesScanner $classesScanner,
        PhpScanner $phpScanner,
        DirectoryScanner $directoryScanner,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        $data = []
    ) {
        $this->data = $data;
        $this->classesScanner = $classesScanner;
        $this->phpScanner = $phpScanner;
        $this->directoryScanner = $directoryScanner;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritdoc}
     */
    public function doOperation()
    {
        if (array_diff(array_keys($this->data), ['filePatterns', 'paths', 'excludePatterns'])
            !== array_diff(['filePatterns', 'paths', 'excludePatterns'], array_keys($this->data))) {
            return;
        }

        foreach ($this->data['paths'] as $k => $paths) {
            if (!is_array($paths)) {
                $paths = (array)$paths;
            }
            $files = [];

            $cacheDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/di_cache/';
            $fileName = $cacheDir . 'diCompileAppCode_' . $k;
            if (!file_exists($cacheDir)) {
                mkdir($cacheDir, 0777, true);
            }
            $cacheContent = '';
            if (file_exists($fileName)) {
                $cacheContent = file_get_contents($fileName);
            }
            $magentoEntities = [];
            $magentoFiles = [];
            if ($cacheContent) {
                $magentoEntities = json_decode($cacheContent, true);
            }

            foreach ($paths as $key => $path) {
                if (strpos($key, 'Magento_') === 0) {
                    if ($magentoEntities) {
                        continue;
                    }
                    $this->classesScanner->getList($path);
                    $magentoFiles = array_merge_recursive(
                        $magentoFiles,
                        $this->directoryScanner->scan($path, $this->data['filePatterns'], $this->data['excludePatterns'])
                    );
                } else {
                    $this->classesScanner->getList($path);
                    $files = array_merge_recursive(
                        $files,
                        $this->directoryScanner->scan($path, $this->data['filePatterns'], $this->data['excludePatterns'])
                    );
                }
            }

            if ($magentoFiles) {
                $magentoEntities = $this->phpScanner->collectEntities($magentoFiles['php']);
                file_put_contents($fileName, json_encode($magentoEntities));
            }

            $entities = array_merge($magentoEntities, $this->phpScanner->collectEntities($files['php']));
            foreach ($entities as $entityName) {
                class_exists($entityName);
            }
        }
    }

    /**
     * Returns operation name
     *
     * @return string
     */
    public function getName()
    {
        return 'Application code generator';
    }
}
