<?php
namespace Eleanorsoft\DiCompile\Module\Di\App\Task\Operation;

use Magento\Setup\Module\Di\App\Task\OperationInterface;

class InterceptionCache implements OperationInterface
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @var \Magento\Framework\Interception\Config\Config
     */
    private $configInterface;

    /**
     * @var \Magento\Setup\Module\Di\Code\Reader\Decorator\Interceptions
     */
    private $interceptionsInstancesNamesList;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @param \Magento\Framework\Interception\Config\Config $configInterface
     * @param \Magento\Setup\Module\Di\Code\Reader\Decorator\Interceptions $interceptionsInstancesNamesList
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Interception\Config\Config $configInterface,
        \Magento\Setup\Module\Di\Code\Reader\Decorator\Interceptions $interceptionsInstancesNamesList,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        array $data = []
    ) {
        $this->configInterface = $configInterface;
        $this->interceptionsInstancesNamesList = $interceptionsInstancesNamesList;
        $this->data = $data;
        $this->directoryList = $directoryList;
    }

    /**
     * Flushes interception cached configuration and generates a new one
     *
     * @return void
     */
    public function doOperation()
    {
        if (empty($this->data)) {
            return;
        }

        $definitions = [];
        foreach ($this->data as $k => $paths) {
            if (!is_array($paths)) {
                $paths = (array)$paths;
            }

            $cacheDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/di_cache/';
            $fileName = $cacheDir . 'diCompileInterceptionCache_' . $k;
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
                    $magentoFiles = array_merge($magentoFiles, $this->interceptionsInstancesNamesList->getList($path));
                } else {
                    $definitions = array_merge($definitions, $this->interceptionsInstancesNamesList->getList($path));
                }
            }

            if ($magentoFiles) {
                $magentoEntities = $magentoFiles;
                file_put_contents($fileName, json_encode($magentoEntities));
            }

            $definitions = array_merge($definitions, $magentoEntities);
        }

        $this->configInterface->initialize($definitions);
    }

    /**
     * Returns operation name
     *
     * @return string
     */
    public function getName()
    {
        return 'Interception cache generation';
    }
}
