<?php
/**
 * Created by PhpStorm.
 * User: pablogutierrez
 * Date: 2020-02-09
 * Time: 23:06
 */

namespace Bananacode\RedLogistic\Model;

use Bananacode\RedLogistic\Api\RedLogisticInterface;
use Bananacode\RedLogistic\Helper\WebService;
use Magento\Framework\App\Response\Http\FileFactory;

class RedLogistic implements RedLogisticInterface
{
    /**
     * @var FileFactory
     */
    protected $fileFactory;

    /**
     * @var WebService
     */
    protected $_webService;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;

    /**
     * RedLogistic constructor.
     * @param WebService $webService
     * @param FileFactory $fileFactory
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        WebService $webService,
        FileFactory $fileFactory,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
    )
    {
        $this->fileFactory = $fileFactory;
        $this->_webService = $webService;
        $this->_orderRepository = $orderRepository;
    }

    /**
     * @return array|mixed
     */
    public function provinces()
    {
        return $this->_webService->getProvinces();
    }

    /**
     * @param string $province_code
     * @return array
     */
    public function cities($province_code)
    {
        return $this->_webService->getCities($province_code);
    }

    /**
     * @param string $province_code
     * @param string $city_code
     * @return array
     */
    public function districts($province_code, $city_code)
    {
        return $this->_webService->getDistricts($province_code, $city_code);
    }

    /**
     *
     * Track redlogistic package
     *
     * @param string $number
     * @return array result
     */
    public function track($number)
    {
        return $this->_webService->track($number);
    }

    /**
     *
     * Generate redlogistic web guide for order
     *
     * @param string $order_id
     * @return mixed result
     */
    public function guide($order_id)
    {
        $order = $this->_orderRepository->get($order_id);
        if($order->getIncrementId()) {
            if ($guideCode = $this->_webService->generateGuide($order)) {
                $order->setRedlogisticWebGuide($guideCode);
                $this->_orderRepository->save($order);
            } else {
                $order->setRedlogisticWebGuide('ERROR');
                $this->_orderRepository->save($order);
            }
        }
        return true;
    }
}

