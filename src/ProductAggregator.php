<?php

namespace Horoshop;

use Horoshop\Exceptions\UnavailablePageException;

class ProductAggregator
{
    /**
     * @var string
     */
    private $filename;

    /**
     * ProductAggregator constructor.
     *
     * @param string $filename
     */
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    /**
     * @param string $currency
     * @param int    $page
     * @param int    $perPage
     *
     * @return string Json
     * @throws UnavailablePageException
     */
    public function find(string $currency, int $page, int $perPage): string 
    {
        $array = json_decode($this->filename, true);
      
      $currencies = $array['currencies'];
      $categories = $array['categories'];
      $products   = $array['products'];
      $discounts  = $array['discounts'];
      
      foreach($products as $key => $product){
        foreach($categories as $category){
          
          if($product['category'] === $category['id']){
          $arrays['items'][$key]['id'] = $product['id'];
          $arrays['items'][$key]['title'] = $product['title'];
          $arrays['items'][$key]['categories'] = $category;
          $arrays['items'][$key]['price']['amount'] = $product['amount'];
          
          $arrays['items'][$key]['price'] = $this->calculetePrice($currency, $currencies, $product, $discounts);
          
          }
        }
        
      }

      $pages = count($arrays['items']) / $perPage;
      $result = array_chunk($arrays['items'], $perPage, true);
      
      $json['item'] = $result[$page - 1];
      $json['perPage'] = $perPage;
      $json['pages'] = $pages;
      $json['page'] = $page;
      
      return json_encode($json);
    }
    
    protected function calculetePrice(string $currency, array $currencies, array $product, array $discounts):array
    {
          $price['amount'] = $product['amount'];
          
          $discCategory = $discounts[array_search($product['category'], array_column($discounts, 'related_id'))];
          $discProduct  = $discounts[array_search($product['id'], array_column($discounts, 'related_id'))];
          
          
          if($discCategory['type'] == 'percent'){
            $amountCategory = $product['amount'] - $product['amount']/100 * $discCategory['value'];
          }else{
            $amountCategory = $product['amount'] - $discCategory['value'];
          }
          
          if($discProduct['type'] == 'percent'){
            $amountProduct = $product['amount'] - $product['amount']/100 * $discProduct['value'];
          }else{
            $amountProduct = $product['amount'] - $discProduct['value'];
          }
          
          $price['currency'] = $currency;
          
          switch($amountProduct <=> $amountCategory){
            case 0:
            case 1:
              $price['discounted_price'] = $this->calculeteCurrency($currency, $currencies, $amountProduct);
              $price['discount'] = $discProduct;
              break;
            case -1:
              $price['discounted_price'] = $this->calculeteCurrency($currency, $currencies, $amountCategory);
              $price['discount'] = $discCategory;
              break;
          }
          
          return $price;
    }
    
    protected function calculeteCurrency(string $currency, array $currencies, $value)
    {
      if($currency == 'UAH'){
        return $value;
      }else{
        $rates = array_shift($currencies);
        $value = $value * $rates['rates'][$currency];
        
        return round($value, 2, PHP_ROUND_HALF_UP);
      }
    }
}