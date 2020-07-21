<?php

namespace Bananacode\RedLogistic\Observer;

use Bananacode\RedLogistic\Helper\WebService;
use \Magento\Framework\Event\Observer;
use \Magento\Framework\Event\ObserverInterface;

/**
 * Class SendOrderRedLogistic
 * @package Bananacode\Ciclica\Observer
 */
class SendOrderRedLogistic implements ObserverInterface
{
    /**
     * @var \Magento\Framework\Webapi\ServiceOutputProcessor
     */
    protected $_serviceOutputProcessor;

    /**
     * @var WebService
     */
    protected $_webService;

    /**
     * @var \Magento\Checkout\Api\Data\ShippingInformationInterface
     */
    protected $_shippingInformation;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $_orderRepository;


    /**
     * SendOrderRedLogistic constructor.
     * @param \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor
     * @param \Magento\Checkout\Api\Data\ShippingInformationInterface $_shippingInformation
     * @param \Magento\Sales\Api\OrderRepositoryInterface $orderRepository
     * @param WebService $webService
     */
    public function __construct
    (
        \Magento\Framework\Webapi\ServiceOutputProcessor $serviceOutputProcessor,
        \Magento\Checkout\Api\Data\ShippingInformationInterface $_shippingInformation,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        WebService $webService
    )
    {
        $this->_serviceOutputProcessor = $serviceOutputProcessor;
        $this->_webService = $webService;
        $this->_shippingInformation = $_shippingInformation;
        $this->_orderRepository = $orderRepository;
    }

    /**
     * @param Observer $observer
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /**
         * @var \Magento\Sales\Model\Order $order
         */
        $order = $observer->getEvent()->getOrder();

        /**
         * @var \Magento\Quote\Model\Quote $quote
         */
        $quote = $observer->getEvent()->getQuote();

        if ($order && $quote) {
            if($order->getShippingMethod() == 'redlogistic_redlogistic') {
                $order->setRedlogisticDistrict($quote->getShippingAddress()->getRedlogisticDistrict());
                $this->_orderRepository->save($order);
            }
        }
    }
}
