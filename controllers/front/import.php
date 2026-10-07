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

class m4p_addtocartfromfileImportModuleFrontController extends ModuleFrontController
{
    public $ajax = true;

    /**
     * Takes the whole parsed file in one request and answers with one result per row.
     *
     * Rows arrive already split, because the spreadsheet parser runs in the browser.
     */
    public function postProcess()
    {
        // Every guest of a shop shares one front token, so the JSON content type is
        // what actually blocks a cross-origin post.
        if (stripos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'application/json') !== 0) {
            $this->respond(['error' => 'content_type'], 415);
        }

        $payload = json_decode(Tools::file_get_contents('php://input'), true);

        if (!is_array($payload) || !hash_equals(Tools::getToken(false), (string) ($payload['token'] ?? ''))) {
            $this->respond(['error' => 'token'], 403);
        }

        if (!(int) Configuration::get('M4P_ADDTOCARTFROMFILE_ACTIVE')) {
            $this->respond(['error' => 'disabled'], 403);
        }

        $rows = isset($payload['rows']) && is_array($payload['rows']) ? $payload['rows'] : [];
        if (!$rows) {
            $this->respond(['error' => 'empty'], 400);
        }

        $cart = $this->currentCart();
        if (!Validate::isLoadedObject($cart)) {
            $this->respond(['error' => 'cart'], 500);
        }

        $limit = max(1, (int) Configuration::get('M4P_ADDTOCARTFROMFILE_MAX_ROWS'));
        $results = [];

        foreach (array_slice($rows, 0, $limit) as $row) {
            $results[] = $this->importRow($cart, is_array($row) ? $row : []);
        }

        foreach (array_slice($rows, $limit) as $row) {
            $results[] = [
                'label' => $this->rowLabel(is_array($row) ? $row : []),
                'requested' => 0,
                'in_cart' => 0,
                'status' => 'skipped',
            ];
        }

        $this->respond([
            'results' => $results,
            'cart_total' => (int) $cart->nbProducts(),
        ], 200);
    }

    /** Resolves one row to a product or combination and moves it into the cart. */
    protected function importRow(Cart $cart, array $row)
    {
        $reference = trim((string) ($row[0] ?? ''));
        $ean = trim((string) ($row[1] ?? ''));
        $quantity = trim((string) ($row[2] ?? ''));
        $label = $this->rowLabel($row);

        $result = ['label' => $label, 'requested' => 0, 'in_cart' => 0, 'status' => 'error'];

        if ($reference === '' && $ean === '') {
            $result['status'] = 'not_found';

            return $result;
        }

        $quantity = (int) str_replace([' ', ','], ['', '.'], $quantity);
        if ($quantity < 1) {
            $result['status'] = 'bad_quantity';

            return $result;
        }
        $result['requested'] = $quantity;

        $match = $this->findProduct($reference, $ean);
        if ($match === null) {
            $result['status'] = 'not_found';

            return $result;
        }

        [$idProduct, $idAttribute, $minimal] = $match;
        $product = new Product($idProduct, true, $this->context->language->id);

        if (!$product->active || !$product->available_for_order) {
            $result['status'] = 'unavailable';

            return $result;
        }

        $inCart = (int) $cart->getProductQuantity($idProduct, $idAttribute)['quantity'];
        $result['in_cart'] = $inCart;

        $minimal = max(1, (int) ($minimal ?: $product->minimal_quantity));

        $wanted = $quantity;

        if (!Product::isAvailableWhenOutOfStock((int) $product->out_of_stock)) {
            $available = (int) StockAvailable::getQuantityAvailableByProduct($idProduct, $idAttribute);
            $room = $available - $inCart;

            if ($room <= 0) {
                $result['status'] = 'out_of_stock';

                return $result;
            }

            $wanted = min($quantity, $room);
        }

        if ($inCart + $wanted < $minimal) {
            $result['status'] = 'min_quantity';

            return $result;
        }

        if (!$cart->updateQty($wanted, $idProduct, $idAttribute, false, 'up')) {
            $result['status'] = 'error';

            return $result;
        }

        $result['in_cart'] = (int) $cart->getProductQuantity($idProduct, $idAttribute)['quantity'];
        $result['status'] = $wanted < $quantity ? 'reduced' : 'imported';

        return $result;
    }

    /**
     * Resolves a row to a product and combination, combination first.
     *
     * A B2B catalogue usually gives every combination its own reference, and the
     * cart rejects a product that has combinations unless one is named.
     *
     * @return array{0:int,1:int,2:int}|null [id_product, id_product_attribute, minimal_quantity]
     */
    protected function findProduct($reference, $ean)
    {
        foreach ([['reference', $reference], ['ean13', $ean]] as [$column, $value]) {
            if ($value === '') {
                continue;
            }

            $combination = Db::getInstance()->getRow(
                (new DbQuery())
                    ->select('pa.id_product, pa.id_product_attribute, pas.minimal_quantity')
                    ->from('product_attribute', 'pa')
                    ->innerJoin('product_attribute_shop', 'pas', 'pas.id_product_attribute = pa.id_product_attribute AND pas.id_shop = ' . (int) $this->context->shop->id)
                    ->where('pa.' . $column . ' = "' . pSQL($value) . '"')
            );

            if ($combination) {
                return [
                    (int) $combination['id_product'],
                    (int) $combination['id_product_attribute'],
                    (int) $combination['minimal_quantity'],
                ];
            }

            $product = Db::getInstance()->getRow(
                (new DbQuery())
                    ->select('p.id_product')
                    ->from('product', 'p')
                    ->innerJoin('product_shop', 'ps', 'ps.id_product = p.id_product AND ps.id_shop = ' . (int) $this->context->shop->id)
                    ->where('p.' . $column . ' = "' . pSQL($value) . '"')
            );

            if ($product) {
                $idProduct = (int) $product['id_product'];
                $idAttribute = (int) Product::getDefaultAttribute($idProduct);

                return [$idProduct, $idAttribute, $this->combinationMinimalQty($idAttribute)];
            }
        }

        return null;
    }

    protected function combinationMinimalQty($idAttribute)
    {
        if (!$idAttribute) {
            return 0;
        }

        return (int) Db::getInstance()->getValue(
            (new DbQuery())
                ->select('minimal_quantity')
                ->from('product_attribute_shop')
                ->where('id_product_attribute = ' . (int) $idAttribute)
                ->where('id_shop = ' . (int) $this->context->shop->id)
        );
    }

    /** What the customer wrote in the row, so they can find it in their own file. */
    protected function rowLabel(array $row)
    {
        $reference = trim((string) ($row[0] ?? ''));
        $ean = trim((string) ($row[1] ?? ''));

        return $reference !== '' ? $reference : $ean;
    }

    protected function currentCart()
    {
        $cart = $this->context->cart;

        if (Validate::isLoadedObject($cart) && $cart->id) {
            return $cart;
        }

        $cart = new Cart();
        $cart->id_customer = (int) $this->context->customer->id;
        $cart->id_address_delivery = (int) Address::getFirstCustomerAddressId($cart->id_customer);
        $cart->id_address_invoice = $cart->id_address_delivery;
        $cart->id_lang = (int) $this->context->language->id;
        $cart->id_currency = (int) $this->context->currency->id;
        $cart->id_guest = (int) $this->context->cookie->id_guest;
        $cart->id_shop_group = (int) $this->context->shop->id_shop_group;
        $cart->id_shop = (int) $this->context->shop->id;
        $cart->add();

        $this->context->cart = $cart;
        $this->context->cookie->id_cart = (int) $cart->id;

        return $cart;
    }

    protected function respond(array $data, $status)
    {
        http_response_code((int) $status);
        $this->ajaxRender(json_encode($data));
        exit;
    }
}
