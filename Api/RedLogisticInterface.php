<?php

namespace Bananacode\RedLogistic\Api;

/**
 * Created by PhpStorm.
 * User: pablogutierrez
 * Date: 2020-02-09
 * Time: 23:02
 */

interface RedLogisticInterface
{
    /**
     * Returns CR provinces
     *
     * @api
     * @return array provinces
     */
    public function provinces();

    /**
     * Returns province cities
     *
     * @api
     * @param string $province_code province code.
     * @return array province cities
     */
    public function cities($province_code);

    /**
     * Returns city districts
     *
     * @api
     * @param string $province_code province code.
     * @param string $city_code city code.
     * @return array city districts
     */
    public function districts($province_code, $city_code);

    /**
     *
     * Track redlogistic package
     *
     * @param string $number
     * @return array result
     */
    public function track($number);

    /**
     *
     * Generate redlogistic web guide for order
     *
     * @param string $order_id
     * @return mixed result
     */
    public function guide($order_id);
}
