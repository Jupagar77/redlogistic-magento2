<?php
/**
 * Created by PhpStorm.
 * User: pablogutierrez
 * Date: 2020-02-09
 * Time: 23:06
 */

namespace Bananacode\RedLogistic\Model\Carrier;

use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;

class RedLogistic extends AbstractCarrier implements CarrierInterface
{
    CONST LOCATIONS_RATES = BP . '/app/code/Bananacode/RedLogistic/Model/Carrier/red_logistic_rates.json';

    /**
     * Costa Rica Provinces
     */
    const PROVINCES = [
        "San José" => 1,
        "Alajuela" => 2,
        "Cartago" => 3,
        "Heredia" => 4,
        "Guanacaste" => 5,
        "Puntarenas" => 6,
        "Limón" => 7,
    ];

    /**
     * @var string
     */
    protected $_code = 'redlogistic';

    /**
     * @var bool
     */
    protected $_isFixed = false;

    /**
     * @var \Bananacode\RedLogistic\Helper\WebService
     */
    protected $_webService;

    /**
     * @var \Magento\Shipping\Model\Rate\ResultFactory
     */
    private $rateResultFactory;

    /**
     * @var \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory
     */
    private $rateMethodFactory;

    /**
     * @var \Magento\Checkout\Model\Session
     */
    private $_checkoutSession;

    /**
     * RedLogistic constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Bananacode\RedLogistic\Helper\WebService $webService
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Bananacode\RedLogistic\Helper\WebService $webService,
        \Magento\Checkout\Model\Session $checkoutSession,
        array $data = []
    )
    {
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);

        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        $this->_webService = $webService;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param RateRequest $request
     * @return bool|\Magento\Framework\DataObject|\Magento\Shipping\Model\Rate\Result|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        if($rates = file_get_contents(self::LOCATIONS_RATES)) {
            $locations = json_decode($rates);
            foreach ($locations as $location) {
                if($request->getDestPostcode() == $location->CODIGO) {
                    switch ($location->ESTATUS) {
                        case 'RURAL' :
                            $shippingCost = $this->_scopeConfig->getValue('carriers/redlogistic/shipping_cost_out');
                            $extraKg = $this->_scopeConfig->getValue('carriers/redlogistic/shipping_cost_out_kg');
                            break;
                        case 'URBANO' :
                            $shippingCost = $this->_scopeConfig->getValue('carriers/redlogistic/shipping_cost');
                            $extraKg = $this->_scopeConfig->getValue('carriers/redlogistic/shipping_cost_kg');
                            break;
                        case 'ESPECIAL' :
                            $shippingCost = $this->_scopeConfig->getValue('carriers/redlogistic/shipping_cost_special');
                            $extraKg = $this->_scopeConfig->getValue('carriers/redlogistic/shipping_cost_special_kg');
                            break;
                        default: return false;
                    }

                    if ($request->getPackageWeight() > 1) {
                        $extraKgs = ceil($request->getPackageWeight());
                        for ($i = 1; $i < $extraKgs; $i++) {
                            $shippingCost += $extraKg;
                        }
                    }

                    /**
                     * Fee
                     */
                    $shippingCost = (floatval($shippingCost) * 0.10) + floatval($shippingCost);

                    /**
                     * Discount code
                     */
                    $quote = $this->_checkoutSession->getQuote();
                    if($quote->getCouponCode()) {
                        if($quote->getCouponCode() === $this->_scopeConfig->getValue('carriers/redlogistic/coupon')) {
                            $shippingCost = 0;
                        }
                    }

                    /** @var \Magento\Shipping\Model\Rate\Result $result */
                    $result = $this->rateResultFactory->create();

                    /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
                    $method = $this->rateMethodFactory->create();

                    $method->setCarrier($this->_code);
                    $method->setCarrierTitle($this->getConfigData('title'));

                    $method->setMethod($this->_code);
                    $method->setMethodTitle($this->getConfigData('name'));

                    $method->setPrice($shippingCost);
                    $method->setCost($shippingCost);

                    $result->append($method);

                    return $result;
                }
            }
        } else {
            return false;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return [$this->_code => $this->getConfigData('name')];
    }
}
