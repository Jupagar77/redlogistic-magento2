<?php

namespace Bananacode\RedLogistic\Plugin\Block\Checkout;

use Bananacode\RedLogistic\Helper\WebService;

/**
 * Class LayoutProcessor
 * @package Bananacode\RedLogistic\Plugin\Block\Checkout
 */
class LayoutProcessor
{
    /**
     * @var WebService
     */
    protected $_webService;

    /**
     * LayoutProcessor constructor.
     * @param WebService $webService
     */
    public function __construct(
        WebService $webService
    )
    {
        $this->_webService = $webService;
    }

    /**
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $processor
     * @param $jsLayout
     * @return array
     */
    public function afterProcess(\Magento\Checkout\Block\Checkout\LayoutProcessor $processor, $jsLayout)
    {
        /**
         * Province input
         */
        $provinces = $this->_webService->getProvinces();
        $provinceCode = 'province';
        if ($provinces) {
            $province = [
                'component' => 'Magento_Ui/js/form/element/select',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'customEntry' => null,
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/select',
                    'tooltip' => false,
                ],
                'dataScope' => 'shippingAddress.custom_attributes' . '.' . $provinceCode,
                'label' => __('Province'),
                'provider' => 'checkoutProvider',
                'sortOrder' => 110,
                'validation' => [
                    'required-entry' => true
                ],
                'options' => $provinces,
                'filterBy' => null,
                'customEntry' => null,
                'visible' => true,
                'value' => ''
            ];
        } else {
            $province = [
                'component' => 'Magento_Ui/js/form/element/abstract',
                'config' => [
                    'customScope' => 'shippingAddress.custom_attributes',
                    'template' => 'ui/form/field',
                    'elementTmpl' => 'ui/form/element/input'
                ],
                'dataScope' => 'shippingAddress.custom_attributes' . '.' . $provinceCode,
                'label' => __('Province'),
                'provider' => 'checkoutProvider',
                'sortOrder' => 110,
                'validation' => [
                    'required-entry' => true
                ],
                'options' => '',
                'filterBy' => null,
                'customEntry' => null,
                'visible' => true,
                'value' => ''
            ];
        }

        /**
         * City input
         */
        $cantonCode = 'canton';
        $canton = $province;
        if($provinces) {
            $canton['options'] = [
                [
                    'value' => '0',
                    'label' => __('Choose canton')
                ]
            ];
        }
        $canton['label'] = __('Canton');
        $canton['sortOrder'] = 111;
        $canton['dataScope'] = 'shippingAddress.custom_attributes' . '.' . $cantonCode;

        /**
         * District input
         */
        $districtCode = 'district';
        $district = $province;
        if($provinces) {
            $district['options'] = [
                [
                    'value' => '0',
                    'label' => __('Choose district')
                ]
            ];
        }
        $district['label'] = __('District');
        $district['sortOrder'] = 112;
        $district['dataScope'] = 'shippingAddress.custom_attributes' . '.' . $districtCode;

        $jsLayout['components']['checkout']['children']['steps']['children']
        ['shipping-step']['children']['shippingAddress']['children']
        ['shipping-address-fieldset']['children'][$districtCode] = $district;

        $jsLayout['components']['checkout']['children']['steps']['children']
        ['shipping-step']['children']['shippingAddress']['children']
        ['shipping-address-fieldset']['children'][$cantonCode] = $canton;

        $jsLayout['components']['checkout']['children']['steps']['children']
        ['shipping-step']['children']['shippingAddress']['children']
        ['shipping-address-fieldset']['children'][$provinceCode] = $province;

        return $jsLayout;
    }
}
