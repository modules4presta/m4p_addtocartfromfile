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

function upgrade_module_2_1_0($module)
{
    return Configuration::updateValue('M4P_ADDTOCARTFROMFILE_MODULE_CSS', 1);
}
