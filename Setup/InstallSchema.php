<?php

namespace Bananacode\RedLogistic\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

/**
 * Class InstallSchema
 * @package Bananacode\RedLogistic\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    ) {
        $installer = $setup;
        $installer->startSetup();

        $connection = $installer->getConnection();

        /**
         * Quote Address
         */

        $connection->addColumn(
            $installer->getTable('quote_address'),
            'redlogistic_district',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'default' => NULL,
                'length' => 255,
                'comment' => 'District RedLogistic CR'
            ]
        );

        /**
         * Sales Order
         */

        $connection->addColumn(
            $installer->getTable('sales_order'),
            'redlogistic_web_guide',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'default' => NULL,
                'length' => 255,
                'comment' => 'WebGuide RedLogistic CR'
            ]
        );

        $connection->addColumn(
            $installer->getTable('sales_order'),
            'redlogistic_district',
            [
                'type' => Table::TYPE_TEXT,
                'nullable' => true,
                'default' => NULL,
                'length' => 255,
                'comment' => 'District RedLogistic CR'
            ]
        );

        $installer->endSetup();
    }
}
