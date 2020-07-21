<?php

namespace Bananacode\RedLogistic\Helper;

class WebService extends \Magento\Framework\App\Helper\AbstractHelper
{
    CONST LOGGER_PATH = BP . '/var/log/redlogistic.log';

    CONST CR_API = 'https://ubicaciones.paginasweb.cr/';

    /**
     * @var \Zend\Log\Logger
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Encryption\EncryptorInterface
     */
    protected $_encryptor;

    /**
     * @var \Magento\Framework\App\CacheInterface
     */
    protected $_cache;

    /**
     * @var \Magento\Framework\DataObjectFactory
     */
    protected $_dataObjectFactory;

    /**
     * @var \Magento\Framework\HTTP\Client\Curl
     */
    public $_curl;

    /**
     * WebService constructor.
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Framework\Encryption\EncryptorInterface $encryptor
     * @param \Magento\Framework\DataObjectFactory $dataObjectFactory
     * @param \Magento\Framework\App\CacheInterface $cache
     * @param \Magento\Framework\HTTP\Client\Curl $curl
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Encryption\EncryptorInterface $encryptor,
        \Magento\Framework\DataObjectFactory $dataObjectFactory,
        \Magento\Framework\App\CacheInterface $cache,
        \Magento\Framework\HTTP\Client\Curl $curl
    )
    {
        $this->_encryptor = $encryptor;
        parent::__construct($context);

        $this->_dataObjectFactory = $dataObjectFactory;
        $this->_cache = $cache;
        $this->_curl = $curl;

        $writer = new \Zend\Log\Writer\Stream(self::LOGGER_PATH);
        $this->_logger = new \Zend\Log\Logger();
        $this->_logger->addWriter($writer);
    }

    /**
     * @return array|mixed
     */
    public function getProvinces()
    {
        if ($data = $this->_cache->load('redLogisticProvince')) {
            $this->_logger->info(print_r('Cache hit!', true));
            return json_decode($data);
        } else {
            $this->_curl->get(self::CR_API . 'provincias.json');
            $provinces = (Array)json_decode($this->_curl->getBody());
            $responseProvinces = [];
            foreach ($provinces as $index => $province) {
                $responseProvinces[$index]['value'] = $index;
                $responseProvinces[$index]['label'] = $province;
            }
            $response = array_values($responseProvinces);

            $this->_logger->info(print_r('Caching: provinces', true));
            $this->_cache->save(json_encode($response), 'redLogisticProvince');

            return $response;
        }
    }

    /**
     * @param $province
     * @return array
     */
    public function getCities($province)
    {
        if ($data = $this->_cache->load('redLogisticCities' . $province)) {
            $this->_logger->info(print_r('Cache hit!', true));
            return array_values(json_decode($data, true));
        } else {
            $this->_curl->get(self::CR_API . 'provincia/' . $province . '/cantones.json');
            $cities = (Array)json_decode($this->_curl->getBody());
            $responseCities = [];
            foreach ($cities as $index => $city) {
                $responseCities[$index]['value'] = $index;
                $responseCities[$index]['label'] = $city;
            }
            $response = array_values($responseCities);

            $this->_logger->info(print_r('Caching: cities', true));
            $this->_cache->save(json_encode($response), 'redLogisticCities' . $province);

            return $response;
        }
    }

    /**
     * @param $province
     * @param $city
     * @return array
     */
    public function getDistricts($province, $city)
    {
        if ($data = $this->_cache->load('redLogisticDistricts' . $province . $city)) {
            $this->_logger->info(print_r('Cache hit!', true));
            return array_values(json_decode($data, true));
        } else {
            $this->_curl->get(self::CR_API . 'provincia/' . $province . '/canton/' . $city . '/distritos.json');
            $districts = (Array)json_decode($this->_curl->getBody());
            $responseDistricts = [];
            foreach ($districts as $index => $district) {
                $responseDistricts[$index]['value'] = $index;
                $responseDistricts[$index]['label'] = $district;
            }
            $response = array_values($responseDistricts);

            $this->_logger->info(print_r('Caching: districs', true));
            $this->_cache->save(json_encode($response), 'redLogisticDistricts' . $province . $city);

            return $response;
        }
    }


    /**
     * @param $number
     * @return bool|mixed
     */
    public function track($number)
    {
        $result = $this->soap('getXML', 'server_wst.php', array(
            "guia" => $number,
            "tokn" => $this->_encryptor->decrypt($this->getConfig('carriers/redlogistic/token')),
        ));

        $this->_logger->info(print_r($result, true));

        if (is_array($result)) {
            if (isset($result['tracking']['miembro'])) {
                return (Array)($result['tracking']['miembro']);
            }
        }

        return false;
    }

    /**
     * @param $order
     * @return bool
     */
    public function generateGuide($order)
    {
        $address = $order->getShippingAddress();
        $content = '';
        foreach ($order->getItems() as $item) {
            $content .= $item->getName() . ' ';
        }

        $result = $this->soap('getXML', 'server_wsi2.php', array(
            //Brand Data
            'nrem' => $this->getConfig('carriers/redlogistic/company_name'),
            'ciuo' => $this->getConfig('carriers/redlogistic/city'),
            'drem' => $this->getConfig('carriers/redlogistic/address'),
            'obse' => $this->getConfig('carriers/redlogistic/observations'),

            //Client Data
            'nomb' => "{$address->getFirstname()} {$address->getLastname()}",
            'dire' => implode(', ', $address->getStreet()),
            'ciud' => $address->getRegion(),
            'depa' => $address->getCity(),
            'tele' => $address->getTelephone(),

            //Package Information
            'kilo' => $order->getWeight(),
            'cont' => $content,

            'serv' => 'MERCANCIA PREMIER',
            'piez' => '1',
            'volu' => '1',
            'tran' => 'TERRESTRE',

            'mpag' => 'CREDITO',
            'vrec' => '0',
            'vdec' => $order->getBaseSubtotal(),

            'remi' => $order->getId(),
            'fact' => $order->getId(),

            'empr' => $this->getConfig('carriers/redlogistic/company_name'),

            'cpos' => ''
        ));

        $this->_logger->info(print_r($result, true));

        if (is_array($result)) {
            if (isset($result['tracking']['miembro']['guia']['value'])) {
                return $result['tracking']['miembro']['guia']['value'];
            }
        }

        return false;
    }

    /**
     * @param $method
     * @param $service
     * @param array $params
     * @return array|bool
     */
    public function soap($method, $service, $params = array())
    {
        $this->_logger->info(print_r('------------------- RedLogistic Call -------------------', true));

        foreach ($params as &$param) {
            $param = $this->removeAccent($param);
        }

        try {
            $mainServiceUrl = $this->getConfig('carriers/redlogistic/url');
            $serviceUrl = $mainServiceUrl . $service;
            include_once('nusoap.php');
            $client = new \nusoap_client("$serviceUrl?wsdl", 'wsdl');
            if ($client->getError()) {
                return false;
            }

            $this->_logger->info(print_r($serviceUrl, true));

            if ($this->getConfig('carriers/redlogistic/sandbox_enabled')) {
                $params['usu'] = $this->getConfig('carriers/redlogistic/sandbox_user');
                $params['pwd'] = $this->_encryptor->decrypt($this->getConfig('carriers/redlogistic/sandbox_pass'));
            } else {
                $params['usu'] = $this->getConfig('carriers/redlogistic/production_user');
                $params['pwd'] = $this->_encryptor->decrypt($this->getConfig('carriers/redlogistic/production_pass'));
            }

            $this->_logger->info(print_r($method, true));
            $this->_logger->info(print_r($params, true));

            $response = $client->call("$method", $params,
                "uri:" . $serviceUrl,
                "uri:" . $serviceUrl . '/' . $method
            );

            $this->_logger->info(print_r($response, true));

            if ($client->fault || !$response) {
                return false;
            }

            return $this->xmlToArray(simplexml_load_string($response));
        } catch (\Exception $e) {
            $this->_logger->info(print_r($e->getMessage(), true));
            return false;
        }
    }

    /**
     * @param $config
     * @return mixed
     */
    public function getConfig($config)
    {
        return $this->scopeConfig->getValue(
            $config,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @param \SimpleXMLElement $xml
     * @return array
     */
    function xmlToArray(\SimpleXMLElement $xml): array
    {
        $parser = function (\SimpleXMLElement $xml, array $collection = []) use (&$parser) {
            $nodes = $xml->children();
            $attributes = $xml->attributes();

            if (0 !== count($attributes)) {
                foreach ($attributes as $attrName => $attrValue) {
                    $collection['attributes'][$attrName] = strval($attrValue);
                }
            }

            if (0 === $nodes->count()) {
                $collection['value'] = strval($xml);
                return $collection;
            }

            foreach ($nodes as $nodeName => $nodeValue) {
                if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
                    $collection[$nodeName] = $parser($nodeValue);
                    continue;
                }

                $collection[$nodeName][] = $parser($nodeValue);
            }

            return $collection;
        };

        return [
            $xml->getName() => $parser($xml)
        ];
    }

    /**
     * @param $str
     * @return string
     */
    function removeAccent($str)
    {
        $unwanted_array = array('Š' => 'S', 'š' => 's', 'Ž' => 'Z', 'ž' => 'z', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E',
            'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U',
            'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'a', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'þ' => 'b', 'ÿ' => 'y');

        return strtr($str, $unwanted_array);
    }
}
