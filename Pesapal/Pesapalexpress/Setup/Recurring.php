<?php
namespace Pesapal\Pesapalexpress\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Sales\Model\Order\StatusFactory;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

class Recurring implements InstallSchemaInterface
{
    /**
     * @var StatusFactory
     */
    private $statusFactory;

    /**
     * @var CollectionFactory
     */
    private $statusCollectionFactory;

    public function __construct(
        StatusFactory $statusFactory,
        CollectionFactory $statusCollectionFactory
    ) {
        $this->statusFactory = $statusFactory;
        $this->statusCollectionFactory = $statusCollectionFactory;
    }

    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        // Add custom order statuses
        $customStatuses = [
            'completed' => __('Completed'),
            'failed' => __('Failed'),
            'invalid' => __('Invalid'),
            'reversed' => __('Reversed'),
        ];

        foreach ($customStatuses as $code => $label) {
            $data[] = ['status' => $code, 'label' => $label];
        }

        $setup->getConnection()->insertMultiple(
            $setup->getTable('sales_order_status'),
            $data
        );

        // Assign custom order statuses to order state 'complete' and make them visible on the storefront
        foreach ($customStatuses as $code => $label) {
            $status = $this->statusFactory->create()->load($code);
            $status->assignState(\Magento\Sales\Model\Order::STATE_COMPLETE, true, true);
        }

        // Get module table
        $tableName = $setup->getTable('sales_order');

        // Check if the table already exists
        if ($setup->getConnection()->isTableExists($tableName) == true) {
            // Declare data
            $columns = [
                'pesapal_transaction_tracking_id' => [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    100,
                    'nullable' => true,
                    'comment' => 'pesapal id',
                ],
            ];

            $connection = $setup->getConnection();
            foreach ($columns as $name => $definition) {
                $connection->addColumn($tableName, $name, $definition);
            }
        }

        $setup->endSetup();
    }
}
