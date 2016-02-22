<?php
namespace Cgi\Application\Controllers;

use Cgi\Application\Core\Controller;
use Cgi\Application\Core\Settings;
use Cgi\Application\Models\MagentoProductModel;

class DataController extends Controller
{
    /**Import products using Magento REST API and save them in the database
     *
     */
    public function actionImport()
    {
        $report = "";
        if(isset($_GET["url"]) && $_GET["url"] != null)
        {
            //forming the request url
            $url = $_GET["url"] . 'api/rest/products?page=1&limit=100';
            //making a request
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            $productMas = json_decode($output, true);

            foreach($productMas as $product ) {
                $entry = new MagentoProductModel();
                $entry->loadBy('sku', $product['sku']);
                $entry->saveProduct($product);
                //If the product is not in the local database
                if ($entry->getId() == null) {
                    $report .= 'Added new product with SKU: ' . $product['sku']
                        . '<br />';
                }
                else {
                    $report .= 'Updated a product with SKU: ' . $product['sku']
                        . '<br />';
                }
            }
        }
        else {
            echo 'Please enter url address';
        }
        $this->view->render('importPageView.php', 'templateView.php', ['report' => $report]);

    }

    public function actionList()
    {
        //Добавить кеширование!
        $products = MagentoProductModel::getAllProducts();
        $products = MagentoProductModel::sort($products);

        $page = 0;

        if (isset($_GET["page"])) {
            $page = $_GET["page"];
        }

        $pagination = false;
        $itemsOnPage = Settings::getSettings('productsPerPage');
        $amountOfPages = ceil(count($products) / $itemsOnPage);

        if (count($products) > $itemsOnPage) {
            $pagination = true;
        }
        if ($pagination) {
            $productsSplited = array_chunk($products, $itemsOnPage);
            $this->view->render(
                'listingPageView.php', 'templateView.php',
                ['products'      => $productsSplited[$page], 'page' => $page,
                 'amountOfPages' => $amountOfPages]
            );
        } else {
            $this->view->render(
                'listingPageView.php', 'templateView.php',
                ['products'      => $products, 'page' => $page,
                 'amountOfPages' => $amountOfPages]
            );
        }
    }

    public function actionEdit()
    {
        $id = $_GET["id"];
        $product = new MagentoProductModel();
        $product->loadBy('product_id', $id);
        if(isset($_POST['sku'])) {
            foreach ($product->getFields() as $field) {
                if (isset($_POST[$field])) {
                    $product->set($field, $_POST[$field]);
                }
            }
            if($product->validate()) {
                $product->save();
                header("Location: http://pmpanel.loc/data/list");
            }
            else echo 'Invalid data!';

        }
        $this->view->render('editingFormView.php', 'templateView.php', $product);

    }

}