<?php
namespace Eleanorsoft\DiCompile\Module\Di\App\Task\Operation;

use Magento\Setup\Module\Di\App\Task\OperationInterface;
use Magento\Framework\App;
use Magento\Setup\Module\Di\Compiler\Config;
use Magento\Setup\Module\Di\Definition\Collection as DefinitionsCollection;

class Area implements OperationInterface
{
    /**
     * @var App\AreaList
     */
    private $areaList;

    /**
     * @var \Magento\Setup\Module\Di\Code\Reader\Decorator\Area
     */
    private $areaInstancesNamesList;

    /**
     * @var Config\Reader
     */
    private $configReader;

    /**
     * @var Config\WriterInterface
     */
    private $configWriter;

    /**
     * @var array
     */
    private $data = [];

    /**
     * @var \Magento\Setup\Module\Di\Compiler\Config\ModificationChain
     */
    private $modificationChain;

    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    private $directoryList;

    /**
     * @param App\AreaList $areaList
     * @param \Magento\Setup\Module\Di\Code\Reader\Decorator\Area $areaInstancesNamesList
     * @param Config\Reader $configReader
     * @param Config\WriterInterface $configWriter
     * @param \Magento\Setup\Module\Di\Compiler\Config\ModificationChain $modificationChain
     * @param App\Filesystem\DirectoryList $directoryList
     * @param array $data
     */
    public function __construct(
        App\AreaList $areaList,
        \Magento\Setup\Module\Di\Code\Reader\Decorator\Area $areaInstancesNamesList,
        Config\Reader $configReader,
        Config\WriterInterface $configWriter,
        Config\ModificationChain $modificationChain,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        $data = []
    ) {
        $this->areaList = $areaList;
        $this->areaInstancesNamesList = $areaInstancesNamesList;
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->data = $data;
        $this->modificationChain = $modificationChain;
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

        $definitionsCollection = new DefinitionsCollection();
        foreach ($this->data as $k => $paths) {
            if (!is_array($paths)) {
                $paths = (array)$paths;
            }

            $cacheDir = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR) . '/di_cache/';
            $fileName = $cacheDir . 'diCompileArea_' . $k;
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
                $magentoEntities = unserialize($cacheContent);
            }

            foreach ($paths as $key => $path) {
                if (strpos($key, 'Magento_') === 0) {
                    if ($magentoEntities) {
                        continue;
                    }
                    $magentoFiles[] = $this->getDefinitionsCollection($path);
                } else {
                    $definitionsCollection->addCollection($this->getDefinitionsCollection($path));
                }
            }

            if ($magentoFiles) {
                $magentoEntities = $magentoFiles;
                file_put_contents($fileName, serialize($magentoEntities));
            }

            foreach ($magentoEntities as $entity) {
                $definitionsCollection->addCollection($entity);
            }
        }

        $areaCodes = array_merge([App\Area::AREA_GLOBAL], $this->areaList->getCodes());
        foreach ($areaCodes as $areaCode) {
            $config = $this->configReader->generateCachePerScope($definitionsCollection, $areaCode);
            $config = $this->modificationChain->modify($config);

            $this->configWriter->write(
                $areaCode,
                $config
            );
        }
    }

    /**
     * Returns definitions collection
     *
     * @param string $path
     * @return DefinitionsCollection
     */
    protected function getDefinitionsCollection($path)
    {
        $definitions = new DefinitionsCollection();
        foreach ($this->areaInstancesNamesList->getList($path) as $className => $constructorArguments) {
            $definitions->addDefinition($className, $constructorArguments);
        }
        return $definitions;
    }

    /**
     * Returns operation name
     *
     * @return string
     */
    public function getName()
    {
        return 'Area configuration aggregation';
    }
}
