<?php

/**
 * m4p_addtocartfromfile
 *
 * @author    Modules4Presta <contact@modules4presta.io>
 * @copyright 2026 Nice Code sp. z o.o. (Modules4Presta)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class m4p_addtocartfromfile extends Module
{
    const CONFIG_KEYS = [
        'M4P_ADDTOCARTFROMFILE_ACTIVE',
        'M4P_ADDTOCARTFROMFILE_CSV',
        'M4P_ADDTOCARTFROMFILE_XLS',
        'M4P_ADDTOCARTFROMFILE_MAX_ROWS',
    ];

    const DEFAULTS = [
        'M4P_ADDTOCARTFROMFILE_ACTIVE' => 1,
        'M4P_ADDTOCARTFROMFILE_CSV' => 1,
        'M4P_ADDTOCARTFROMFILE_XLS' => 1,
        'M4P_ADDTOCARTFROMFILE_MAX_ROWS' => 500,
    ];

    /** Settings used by the private releases, removed on install once read. */
    const LEGACY_KEYS = [
        'M4P_ADDTOCARTFROMFILE_ACTIVE' => 'm4p_addtocartfromfile_switch',
        'M4P_ADDTOCARTFROMFILE_CSV' => 'm4p_addtocartfromfile_csv',
        'M4P_ADDTOCARTFROMFILE_XLS' => 'm4p_addtocartfromfile_xls',
    ];

    public function __construct()
    {
        $this->name = 'm4p_addtocartfromfile';
        $this->tab = 'front_office_features';
        $this->version = '2.0.0';
        $this->author = 'Modules4Presta';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '1.7.6.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->trans('Add to cart from a file', [], 'Modules.M4paddtocartfromfile.Admin');
        $this->description = $this->trans('Lets customers fill their cart from a CSV or spreadsheet file with product references and quantities.', [], 'Modules.M4paddtocartfromfile.Admin');
        $this->confirmUninstall = $this->trans('Remove the module and its settings? Carts are not touched.', [], 'Modules.M4paddtocartfromfile.Admin');
    }

    public function install()
    {
        foreach (self::DEFAULTS as $key => $value) {
            Configuration::updateValue($key, $this->legacyValue($key, $value));
        }

        return parent::install()
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayShoppingCartFooter');
    }

    public function uninstall()
    {
        foreach (self::CONFIG_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    /**
     * Keeps the setting a shop already had when it upgrades from the 1.x releases,
     * which used lower-case keys.
     */
    protected function legacyValue($key, $default)
    {
        if (!isset(self::LEGACY_KEYS[$key])) {
            return $default;
        }

        $legacy = self::LEGACY_KEYS[$key];

        if (!Configuration::hasKey($legacy)) {
            return $default;
        }

        $value = (int) Configuration::get($legacy);
        Configuration::deleteByName($legacy);

        return $value;
    }

    public function getContent()
    {
        $output = '';

        if (Tools::isSubmit('submit' . $this->name)) {
            $maxRows = (string) Tools::getValue('M4P_ADDTOCARTFROMFILE_MAX_ROWS', '');
            $csv = (int) Tools::getValue('M4P_ADDTOCARTFROMFILE_CSV', 0);
            $xls = (int) Tools::getValue('M4P_ADDTOCARTFROMFILE_XLS', 0);

            $errors = [];
            if (!Validate::isUnsignedInt($maxRows) || (int) $maxRows < 1) {
                $errors[] = $this->trans('The row limit must be a number greater than zero.', [], 'Modules.M4paddtocartfromfile.Admin');
            }
            if (!$csv && !$xls) {
                $errors[] = $this->trans('Enable at least one file format, otherwise there is nothing customers could upload.', [], 'Modules.M4paddtocartfromfile.Admin');
            }

            if ($errors) {
                foreach ($errors as $error) {
                    $output .= $this->displayError($error);
                }
            } else {
                Configuration::updateValue('M4P_ADDTOCARTFROMFILE_ACTIVE', (int) Tools::getValue('M4P_ADDTOCARTFROMFILE_ACTIVE', 0));
                Configuration::updateValue('M4P_ADDTOCARTFROMFILE_CSV', $csv);
                Configuration::updateValue('M4P_ADDTOCARTFROMFILE_XLS', $xls);
                Configuration::updateValue('M4P_ADDTOCARTFROMFILE_MAX_ROWS', (int) $maxRows);

                Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&conf=6');
            }
        }

        return $output . $this->fileFormatNotice() . $this->displayForm();
    }

    /** Reminder of the column order, so merchants can document it for their customers. */
    protected function fileFormatNotice()
    {
        $lines = [
            $this->trans('Each row is one product: reference, EAN-13, quantity. The first row is treated as a header and skipped.', [], 'Modules.M4paddtocartfromfile.Admin'),
            $this->trans('A row matches on the reference first and falls back to the EAN-13, so one of the two columns is enough.', [], 'Modules.M4paddtocartfromfile.Admin'),
            $this->trans('References of combinations work as well, and quantities above the available stock are reduced instead of rejected.', [], 'Modules.M4paddtocartfromfile.Admin'),
        ];

        return $this->displayInformation(implode('<br>', $lines));
    }

    public function displayForm()
    {
        $switchValues = [
            ['id' => 'on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Modules.M4paddtocartfromfile.Admin')],
            ['id' => 'off', 'value' => 0, 'label' => $this->trans('No', [], 'Modules.M4paddtocartfromfile.Admin')],
        ];

        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->trans('Settings', [], 'Modules.M4paddtocartfromfile.Admin'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->trans('Show the import button', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'name' => 'M4P_ADDTOCARTFROMFILE_ACTIVE',
                    'is_bool' => true,
                    'desc' => $this->trans('Turns the button under the cart on and off.', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'values' => $switchValues,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Accept CSV files', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'name' => 'M4P_ADDTOCARTFROMFILE_CSV',
                    'is_bool' => true,
                    'desc' => $this->trans('Semicolon-separated files, read in the browser.', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'values' => $switchValues,
                ],
                [
                    'type' => 'switch',
                    'label' => $this->trans('Accept XLS and XLSX files', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'name' => 'M4P_ADDTOCARTFROMFILE_XLS',
                    'is_bool' => true,
                    'desc' => $this->trans('Spreadsheets need a parser of about 900 kB, which the cart page loads only once a visitor picks such a file.', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'values' => $switchValues,
                ],
                [
                    'type' => 'text',
                    'label' => $this->trans('Rows per import', [], 'Modules.M4paddtocartfromfile.Admin'),
                    'name' => 'M4P_ADDTOCARTFROMFILE_MAX_ROWS',
                    'class' => 'fixed-width-sm',
                    'desc' => $this->trans('Rows beyond this limit are ignored and reported back to the customer.', [], 'Modules.M4paddtocartfromfile.Admin'),
                ],
            ],
            'submit' => [
                'title' => $this->trans('Save', [], 'Modules.M4paddtocartfromfile.Admin'),
                'class' => 'btn btn-default pull-right',
            ],
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submit' . $this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->trans('Save', [], 'Modules.M4paddtocartfromfile.Admin'),
                'href' => AdminController::$currentIndex . '&configure=' . $this->name . '&save' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex . '&token=' . Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->trans('Back to list', [], 'Modules.M4paddtocartfromfile.Admin'),
            ],
        ];

        $fieldsValue = [];
        foreach (self::CONFIG_KEYS as $key) {
            $fieldsValue[$key] = Tools::getValue($key, Configuration::get($key));
        }

        $helper->tpl_vars = [
            'fields_value' => $fieldsValue,
            'languages' => $this->context->controller->getLanguages(),
        ];

        return $helper->generateForm($fields_form);
    }

    protected function isActive()
    {
        if (!(int) Configuration::get('M4P_ADDTOCARTFROMFILE_ACTIVE')) {
            return false;
        }

        return (bool) Configuration::get('M4P_ADDTOCARTFROMFILE_CSV')
            || (bool) Configuration::get('M4P_ADDTOCARTFROMFILE_XLS');
    }

    public function hookActionFrontControllerSetMedia()
    {
        if (!$this->isActive()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'module-m4p-addtocartfromfile',
            'modules/' . $this->name . '/views/css/main.css',
            ['media' => 'all', 'priority' => 150]
        );
        $this->context->controller->registerJavascript(
            'module-m4p-addtocartfromfile',
            'modules/' . $this->name . '/views/js/main.js',
            ['position' => 'bottom', 'priority' => 150]
        );

        Media::addJsDef([
            'm4pAddToCartFromFile' => [
                'endpoint' => $this->context->link->getModuleLink($this->name, 'import', [], true),
                'token' => Tools::getToken(false),
                'parser' => __PS_BASE_URI__ . 'modules/' . $this->name . '/views/js/vendor/xlsx.full.min.js',
                'csv' => (bool) Configuration::get('M4P_ADDTOCARTFROMFILE_CSV'),
                'xls' => (bool) Configuration::get('M4P_ADDTOCARTFROMFILE_XLS'),
                'maxRows' => (int) Configuration::get('M4P_ADDTOCARTFROMFILE_MAX_ROWS'),
                'labels' => $this->frontLabels(),
            ],
        ]);
    }

    /** Strings the script writes into the DOM after the template is rendered. */
    protected function frontLabels()
    {
        return [
            'selected' => $this->trans('Selected file:', [], 'Modules.M4paddtocartfromfile.Shop'),
            'pick' => $this->trans('Click here to pick a file', [], 'Modules.M4paddtocartfromfile.Shop'),
            'drag' => $this->trans('or drag one onto this field.', [], 'Modules.M4paddtocartfromfile.Shop'),
            'noFile' => $this->trans('Pick a file first.', [], 'Modules.M4paddtocartfromfile.Shop'),
            'badFormat' => $this->trans('This shop does not accept that file format.', [], 'Modules.M4paddtocartfromfile.Shop'),
            'unreadable' => $this->trans('The file could not be read.', [], 'Modules.M4paddtocartfromfile.Shop'),
            'empty' => $this->trans('The file holds no product rows.', [], 'Modules.M4paddtocartfromfile.Shop'),
            'failed' => $this->trans('The import failed. Please try again.', [], 'Modules.M4paddtocartfromfile.Shop'),
            'working' => $this->trans('Importing…', [], 'Modules.M4paddtocartfromfile.Shop'),
            'reportName' => $this->trans('cart-import-report', [], 'Modules.M4paddtocartfromfile.Shop'),
            'imported' => $this->trans('Added', [], 'Modules.M4paddtocartfromfile.Shop'),
            'reduced' => $this->trans('Reduced to available stock', [], 'Modules.M4paddtocartfromfile.Shop'),
            'not_found' => $this->trans('No such product', [], 'Modules.M4paddtocartfromfile.Shop'),
            'out_of_stock' => $this->trans('Out of stock', [], 'Modules.M4paddtocartfromfile.Shop'),
            'unavailable' => $this->trans('Not available for sale', [], 'Modules.M4paddtocartfromfile.Shop'),
            'bad_quantity' => $this->trans('Quantity is not a positive number', [], 'Modules.M4paddtocartfromfile.Shop'),
            'min_quantity' => $this->trans('Below the minimum order quantity', [], 'Modules.M4paddtocartfromfile.Shop'),
            'skipped' => $this->trans('Skipped — over the row limit', [], 'Modules.M4paddtocartfromfile.Shop'),
            'error' => $this->trans('Could not be added', [], 'Modules.M4paddtocartfromfile.Shop'),
        ];
    }

    public function hookDisplayShoppingCartFooter()
    {
        if (!$this->isActive()) {
            return '';
        }

        $accept = [];
        if (Configuration::get('M4P_ADDTOCARTFROMFILE_CSV')) {
            $accept[] = '.csv,text/csv';
        }
        if (Configuration::get('M4P_ADDTOCARTFROMFILE_XLS')) {
            $accept[] = '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        $this->context->smarty->assign([
            'm4p_atcff_accept' => implode(',', $accept),
            'm4p_atcff_example' => __PS_BASE_URI__ . 'modules/' . $this->name . '/views/example-import.csv',
            'm4p_atcff_csv' => (bool) Configuration::get('M4P_ADDTOCARTFROMFILE_CSV'),
            'm4p_atcff_xls' => (bool) Configuration::get('M4P_ADDTOCARTFROMFILE_XLS'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/import.tpl');
    }
}
