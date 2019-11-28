<?php
namespace Eleanorsoft\DiCompile\Module\Di\App\Task\Operation;

use Magento\Setup\Module\Di\App\Task\OperationInterface;
use Magento\Setup\Module\Di\Code\Generator\InterceptionConfigurationBuilder;
use Magento\Framework\Interception\Code\Generator\Interceptor;
use Magento\Framework\App;
use Magento\Setup\Module\Di\Code\GeneratorFactory;
use Magento\Setup\Module\Di\Code\Reader\ClassesScanner;

class Interception implements OperationInterface
{
    /**
     * @var App\AreaList
     */
    private $areaList;

    /**
     * @var InterceptionConfigurationBuilder
     */
    private $interceptionConfigurationBuilder;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var ClassesScanner
     */
    private $classesScanner;

    /**
     * @var GeneratorFactory
     */
    private $generatorFactory;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @param InterceptionConfigurationBuilder $interceptionConfigurationBuilder
     * @param App\AreaList $areaList
     * @param ClassesScanner $classesScanner
     * @param GeneratorFactory $generatorFactory
     * @param App\Filesystem\DirectoryList $directoryList
     * @param array $data
     */
    public function __construct(
        InterceptionConfigurationBuilder $interceptionConfigurationBuilder,
        App\AreaList $areaList,
        ClassesScanner $classesScanner,
        GeneratorFactory $generatorFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        $data = []
    ) {
        $this->interceptionConfigurationBuilder = $interceptionConfigurationBuilder;
        $this->areaList = $areaList;
        $this->data = $data;
        $this->classesScanner = $classesScanner;
        $this->generatorFactory = $generatorFactory;
        $this->directoryList = $directoryList;
    }

    /**
     * {@inheritdoc}
     */
    public function doOperation()
    {
        if (empty($this->data)) {
            return;
        }
        $this->interceptionConfigurationBuilder->addAreaCode(App\Area::AREA_GLOBAL);

        foreach ($this->areaList->getCodes() as $areaCode) {
            $this->interceptionConfigurationBuilder->addAreaCode($areaCode);
        }

        $classesList = [];

        foreach ($this->data['intercepted_paths'] as $k => $paths) {
            if (!is_array($paths)) {
                $paths = (array)$paths;
            }

            $cacheDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/di_cache/';
            $fileName = $cacheDir . 'diCompileInterception_' . $k;
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
                    $magentoFiles = array_merge($magentoFiles, $this->classesScanner->getList($path));
                } else {
                    $classesList = array_merge($classesList, $this->classesScanner->getList($path));
                }
            }

            if ($magentoFiles) {
                $magentoEntities = $magentoFiles;
                file_put_contents($fileName, json_encode($magentoEntities));
            }

            $classesList = array_merge($classesList, $magentoEntities);

        }

        $generatorIo = new \Magento\Framework\Code\Generator\Io(
            new \Magento\Framework\Filesystem\Driver\File(),
            $this->data['path_to_store']
        );
        $generator = $this->generatorFactory->create(
            [
                'ioObject' => $generatorIo,
                'generatedEntities' => [
                    Interceptor::ENTITY_TYPE => 'Magento\Setup\Module\Di\Code\Generator\Interceptor',
                ]
            ]
        );
        $configuration = $this->interceptionConfigurationBuilder->getInterceptionConfiguration($classesList);
        $generator->generateList($configuration);
    }

    /**
     * Returns operation name
     *
     * @return string
     */
    public function getName()
    {
        return 'Interceptors generation';
    }
}
