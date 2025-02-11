<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License 3.0 (AFL-3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
}

class Ps_Googleanalytics extends Module
{
    public $name;
    public $tab;
    public $version;
    public $ps_versions_compliancy;
    public $author;
    public $module_key;
    public $bootstrap;
    public $displayName;
    public $description;
    public $confirmUninstall;
    public $products = [];
    public $_debug = 0;
    private $tools = null;
    private $dataHandler = null;

    public function __construct()
    {
        $this->name = 'ps_googleanalytics';
        $this->tab = 'analytics_stats';
        $this->version = '5.0.2';
        $this->ps_versions_compliancy = ['min' => '1.7.7', 'max' => _PS_VERSION_];
        $this->author = 'PrestaShop';
        $this->module_key = 'fd2aaefea84ac1bb512e6f1878d990b8';
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Google Analytics', [], 'Modules.Googleanalytics.Admin');
        $this->description = $this->trans('Gain clear insights into important metrics about your customers, using Google Analytics', [], 'Modules.Googleanalytics.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall Google Analytics? You will lose all the data related to this module.', [], 'Modules.Googleanalytics.Admin');
    }

    /**
     * Back office module configuration page content
     */
    public function getContent()
    {
        $configurationForm = new PrestaShop\Module\Ps_Googleanalytics\Form\ConfigurationForm($this);
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $output .= $configurationForm->treat();
        }

        $output .= $this->display(__FILE__, './views/templates/admin/configuration.tpl');
        $output .= $configurationForm->generate();

        return $output;
    }

    public function hookDisplayHeader($params, $back_office = false)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayHeader($this, $this->context);
            $hook->setBackOffice($back_office);

            return $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Confirmation page hook.
     * This function is run to track transactions.
     */
    public function hookDisplayOrderConfirmation($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayOrderConfirmation($this, $this->context);
            $hook->setParams($params);

            return $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Footer hook
     * This function is run to load JS script for standards actions such as product clicks
     */
    public function hookDisplayBeforeBodyClosingTag()
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayBeforeBodyClosingTag($this, $this->context);

            return $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Product page footer hook
     * This function is run to load JS for product details view
     */
    public function hookDisplayFooterProduct()
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayFooterProduct($this, $this->context);

            return $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Hook admin order.
     * This function is run to send transactions and refunds details
     */
    public function hookDisplayAdminOrder()
    {
        try {
            $gaTagHandler = new PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsJsHandler($this, $this->context);

            $output = $gaTagHandler->generate($this->context->cookie->ga_admin_refund);
            unset($this->context->cookie->ga_admin_refund);
            $this->context->cookie->write();

            return $output;
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Admin office header hook.
     * This function is run to add Google Analytics JavaScript
     */
    public function hookDisplayBackOfficeHeader()
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookDisplayBackOfficeHeader($this, $this->context);

            return $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Product cancel action hook (in Back office).
     * This function is run to add Google Analytics JavaScript
     */
    public function hookActionProductCancel($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionProductCancel($this, $this->context);
            $hook->setParams($params);
            $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Hook used to detect backoffice orders and store their IDs into cookie.
     */
    public function hookActionValidateOrder($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionValidateOrder($this, $this->context);
            $hook->setParams($params);
            $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Hook called after order status change, used to "refund" order after cancelling it
     */
    public function hookActionOrderStatusPostUpdate($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionOrderStatusPostUpdate($this, $this->context);
            $hook->setParams($params);
            $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Hook to process add and remove items from cart events
     * This function is run to implement 'add to cart' and 'remove from cart' functionalities
     */
    public function hookActionCartUpdateQuantityBefore($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCartUpdateQuantityBefore($this, $this->context);
            $hook->setParams($params);
            $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Hook to process remove items from cart events
     * This function is run to implement 'remove from cart' functionalities
     */
    public function hookActionObjectProductInCartDeleteBefore($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionObjectProductInCartDeleteBefore($this, $this->context);
            $hook->setParams($params);
            $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    public function hookActionCarrierProcess($params)
    {
        try {
            $hook = new PrestaShop\Module\Ps_Googleanalytics\Hooks\HookActionCarrierProcess($this, $this->context);
            $hook->setParams($params);
            $hook->run();
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    protected function _debugLog($function, $log)
    {
        if (!$this->_debug) {
            return true;
        }

        $myFile = _PS_MODULE_DIR_ . $this->name . '/logs/analytics.log';
        $fh = fopen($myFile, 'a');
        fwrite($fh, date('F j, Y, g:i a') . ' ' . $function . "\n");
        fwrite($fh, print_r($log, true) . "\n\n");
        fclose($fh);
    }

    /**
     * This method is triggered at the installation of the module
     * - it installs all module tables
     * - it registers the hooks used by this module
     *
     * @return bool
     */
    public function install()
    {
        $database = new PrestaShop\Module\Ps_Googleanalytics\Database\Install($this);

        return parent::install() &&
            $database->registerHooks() &&
            $database->setDefaultConfiguration() &&
            $database->installTab() &&
            $database->installTables();
    }

    /**
     * Triggered at the uninstall of the module
     * - erases this module SQL tables
     *
     * @return bool
     */
    public function uninstall()
    {
        $database = new PrestaShop\Module\Ps_Googleanalytics\Database\Uninstall();

        return parent::uninstall() &&
            $database->uninstallTab() &&
            $database->uninstallTables();
    }

    /**
     * Returns instance of GoogleAnalyticsTools
     */
    public function getTools()
    {
        if ($this->tools === null) {
            $this->tools = new PrestaShop\Module\Ps_Googleanalytics\GoogleAnalyticsTools();
        }

        return $this->tools;
    }

    /**
     * Returns instance of GanalyticsDataHandler
     */
    public function getDataHandler()
    {
        if ($this->dataHandler === null) {
            $this->dataHandler = new PrestaShop\Module\Ps_Googleanalytics\Handler\GanalyticsDataHandler(
                $this->context->cart->id,
                $this->context->shop->id
            );
        }

        return $this->dataHandler;
    }
}
