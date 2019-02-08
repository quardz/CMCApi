<?php


namespace CMCApi;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7;


class CMCApi {
  private $_secret_key;
  private $_environment;
  private $_environments = array(
    'LIVE',
    'SANDBOX',
  );
  private $_base_urls = array(
    'LIVE' => 'https://pro-api.coinmarketcap.com/',
    'SANDBOX' => 'https://sandbox-api.coinmarketcap.com/',
  );
  private $_base_url;
  private $_api_header_key = 'X-CMC_PRO_API_KEY';
  private $httpClient;
  private $_request_header;
  private $_last_api_error;
  private $_last_error;
  
  //Set the Default Configuration
  public function DefaultConfig($_secret_key, $_environment = 'SANDBOX') {
    $this->_environment = $_environment;
    $this->_base_url = $this->_base_urls[$this->_environment];
    $this->_secret_key = $_secret_key;
  }
  
  public function __construct($_secret_key, $_environment = 'SANDBOX') {
    if($_secret_key) {
      $this->DefaultConfig($_secret_key, $_environment);
    }
    $_request = array(
      'base_uri' => $this->_base_url,
      'headers' => array($this->_api_header_key => $this->_secret_key),
    );

    $this->_request_header = array('headers' => array($this->_api_header_key => $this->_secret_key));

    $this->httpClient = new Client($_request);
  }

  public function getLastAPIError(){
    return $this->_last_api_error;
  }

  public function getLastError(){
    return $this->_last_error;
  }

  //Dump all secret variables
  public function DumpVars(){
    $_loc = array(
      '_secret_key' => $this->_secret_key,
      '_environment' => $this->_environment,
      '_environments' => $this->_environments,
      '_base_urls' => $this->_base_urls,
      '_base_url' => $this->_base_url,
    );
    return print_R($_loc, TRUE);
  }

  /* All the helper function will be start with _ */
  //Get either Id or Symbol to comma sperated item. Input can be either string or Array
  public function _id_or_symbol($ids_symbols, $single = FALSE) {
    if(!$ids_symbols) {
      return FALSE;
    }
    
    if(!is_array($ids_symbols)){
      $ids_symbols = array_unique(explode(',', $ids_symbols));
    }
    
    $return = array('id' => array(), 'symbol' => array());

    if(count($ids_symbols)) {
      foreach($ids_symbols as $_id_symbol) {
        if($_id_symbol) {
          if(is_numeric($_id_symbol)) {
            $return['id'][] = intval($_id_symbol);
          }
          else {
            $return['symbol'][] = trim($_id_symbol);
          }
        }
      }
    }
    if(count($return['id'])) {
      if($single) {
        return array('id'=> $return['id'][0]);
      }      
      unset($return['symbol']);
      $return['id'] = implode(',', $return['id']);
      return $return;
    }
    if(count($return['symbol'])) {
      if($single) {
        return array('symbol'=> $return['symbol'][0]);
      }      
      unset($return['id']);
      $return['symbol'] = implode(',', $return['symbol']);
      return $return;
    }
    return FALSE;
  }

  public function BuildRequest($path, $query) {
    try {
      $response = $this->httpClient->request('GET',$path, ['query'=>$query]);
      if($response->getStatusCode() == 200) {
        $body = $response->getBody();
        return $body->getContents();
      }
    } catch (ClientException $e) {
      $response = $e->getResponse();
      $this->_last_api_error = $response->getBody()->getContents();
      return FALSE;
    }
    return FALSE;
  }

  /* All the cryptocurrency API will be listed here */
  public function cryptocurrency_info($ids_symbols){
    $query = $this->_id_or_symbol($ids_symbols);
    if($query) {
      $path = '/v1/cryptocurrency/info';
      return $this->BuildRequest($path, $query);
    }
    $this->_last_error = 'ID or Symbol is required';
    return FALSE;
  }

    /* All the cryptocurrency API will be listed here */
  public function cryptocurrency_map($listing_status = 'active', $start = 1, $limit = 5000, $symbol = NULL){
    $path = '/v1/cryptocurrency/map';
    $query = array(
      'listing_status' => $listing_status,
      'start' => $start,
      'limit' => $limit,
    );
    if($symbol) {
      $symbol = $this->_id_or_symbol($symbol);
      if($symbol) {
        $query['symbol'] = $symbol;
      }
    }
    return $this->BuildRequest($path, $query);
  }


  public function cryptocurrency_listings_historical(){
    return 'not implemeted';
  }


  // Doc : https://coinmarketcap.com/api/documentation/v1/#operation/getV1CryptocurrencyListingsLatest
  public function cryptocurrency_listings_latest($start = NULL, $limit = NULL, $convert = NULL, $sort = NULL, $sort_dir  = NULL, $cryptocurrency_type = NULL ){
    $path = '/v1/cryptocurrency/listings/latest';
    $query = array();
    if($start) { $query['start'] = $start; }
    if($limit) { $query['limit'] = $limit; }
    if($convert) { $query['convert'] = $convert; }
    if($sort) { $query['sort'] = $sort; }
    if($sort_dir) { $query['sort_dir'] = $sort_dir; }
    if($cryptocurrency_type) { $query['cryptocurrency_type'] = $cryptocurrency_type; }

    return $this->BuildRequest($path, $query);
  }

  // Doc : https://coinmarketcap.com/api/documentation/v1/#operation/getV1CryptocurrencyMarketpairsLatest
  public function cryptocurrency_marketpairs_latest($ids_symbols, $start = NULL, $limit = NULL, $convert = NULL) {
    $query = $this->_id_or_symbol($ids_symbols, TRUE);
    if($query) {
      $path = '/v1/cryptocurrency/market-pairs/latest';
      if($start) { $query['start'] = $start; }
      if($limit) { $query['limit'] = $limit; }
      if($convert) { $query['convert'] = $convert; }
      return $this->BuildRequest($path, $query);
    }
    $this->_last_error = 'ID or Symbol is required';
    return FALSE;  
  }
    
  // All the following are yet to implement 
  public function cryptocurrency_ohlcv_historical(){
    return 'Not ported yet';
  }  
  public function cryptocurrency_ohlcv_latest(){
    return 'Not ported yet';
  }  
  public function cryptocurrency_quotes_historical(){
    return 'Not ported yet';
  }  
  public function cryptocurrency_quotes_latest(){
    return 'Not ported yet';
  }  


  //Everything below are Exchange related stuffs

  //https://coinmarketcap.com/api/documentation/v1/#operation/getV1ExchangeInfo
  public function exchange_info($id_slug){
    $query = $this->_id_or_symbol($id_slug, TRUE);
    if($query) {
      $path = '/v1/exchange/info';
      return $this->BuildRequest($path, $query);
    }
    $this->_last_error = 'Exchange ID or Slug is required';
    return FALSE;      
  }

  public function exchange_map($listing_status = NULL, $slug = NULL, $start = NULL, $limit = NULL){
    $path = '/v1/exchange/map';
    $query = array();
    if($listing_status) { $query['listing_status'] = $listing_status; }
    if($slug) { $query['slug'] = $slug; }
    if($start) { $query['start'] = $start; }
    if($limit) { $query['limit'] = $limit; }
    return $this->BuildRequest($path, $query);
  }

  //GET /v1/exchange/listings/historical
  public function exchange_listings_historical(){
    return 'not implemeted';
  }

  //GET /v1/exchange/listings/latest
  public function exchange_listings_latest($start = NULL, $limit = NULL, $sort = NULL, $sort_dir = NULL, 
    $market_type = NULL, $convert = NULL){
    $path = '/v1/exchange/listings/latest';
    $query = array();
    if($start) { $query['start'] = $start; }
    if($limit) { $query['limit'] = $limit; }
    if($sort) { $query['sort'] = $sort; }
    if($sort_dir) { $query['sort_dir'] = $sort_dir; }
    if($market_type) { $query['market_type'] = $market_type; }
    if($convert) { $query['convert'] = $convert; }    
    return $this->BuildRequest($path, $query);
    
  }

  //GET /v1/exchange/market-pairs/latest
  public function exchange_marketpairs_latest($id_slug, $start = NULL, $limit = NULL, $convert = NULL){
    $query = $this->_id_or_symbol($id_slug, TRUE);
    if($query) {
      $path = '/v1/exchange/market-pairs/latest';
      if($start) { $query['start'] = $start; }
      if($limit) { $query['limit'] = $limit; }
      if($convert) { $query['convert'] = $convert; }
      return $this->BuildRequest($path, $query);
    }
    $this->_last_error = 'ID or Slug is required';
    return FALSE;  
  }

  //GET /v1/exchange/quotes/historical
  public function exchange_quotes_historical(){
    return 'not implemeted';
  }  
  //GET /v1/exchange/quotes/latest
  public function exchange_quotes_latest(){
    return 'not implemeted';
  }  

  // Global Metrics https://coinmarketcap.com/api/documentation/v1/#tag/global-metrics
  //GET /v1/global-metrics/quotes/historical

  public function globalmetrics_quotes_historical() {
    return 'not implemeted';
  }
  
  //GET /v1/global-metrics/quotes/latest
  public function globalmetrics_quotes_latest($convert = NULL) {
    $path = '/v1/global-metrics/quotes/latest';
    $query = array();
    if($convert) { $query['convert'] = $convert; }
    return $this->BuildRequest($path, $query);
  }

  // Tools https://coinmarketcap.com/api/documentation/v1/#tag/tools
  //GET /v1/tools/price-conversion
  public function tools_priceconversion(){
    return 'not implemeted';
  }

  //All the static functions 

  //Get all the fiat currencies supported by Coinmarketcap. 
  public function getAllFiats() {
    $fiat_currencies = array (
      2781 => array (
        'name' => 'United States Dollar',
        'code' => 'USD',
        'symb' => '$',
      ),
      2782 => array (
        'name' => 'Australian Dollar',
        'code' => 'AUD',
        'symb' => '$',
      ),
      2783 => array (
        'name' => 'Brazilian Real',
        'code' => 'BRL',
        'symb' => 'R$',
      ),
      2784 => array (
        'name' => 'Canadian Dollar',
        'code' => 'CAD',
        'symb' => '$',
      ),
      2785 => array (
        'name' => 'Swiss Franc',
        'code' => 'CHF',
        'symb' => 'Fr',
      ),
      2786 => array (
        'name' => 'Chilean Peso',
        'code' => 'CLP',
        'symb' => '$',
      ),
      2787 => array (
        'name' => 'Chinese Yuan',
        'code' => 'CNY',
        'symb' => '¥',
      ),
      2788 => array (
        'name' => 'Czech Koruna',
        'code' => 'CZK',
        'symb' => 'Kč',
      ),
      2789 => array (
        'name' => 'Danish Krone',
        'code' => 'DKK',
        'symb' => 'kr',
      ),
      2790 => array (
        'name' => 'Euro',
        'code' => 'EUR',
        'symb' => '€',
      ),
      2791 => array (
        'name' => 'Pound Sterling',
        'code' => 'GBP',
        'symb' => '£',
      ),
      2792 => array (
        'name' => 'Hong Kong Dollar',
        'code' => 'HKD',
        'symb' => '$',
      ),
      2793 => array (
        'name' => 'Hungarian Forint',
        'code' => 'HUF',
        'symb' => 'Ft',
      ),
      2794 => array (
        'name' => 'Indonesian Rupiah',
        'code' => 'IDR',
        'symb' => 'Rp',
      ),
      2795 => array (
        'name' => 'Israeli New Shekel',
        'code' => 'ILS',
        'symb' => '₪',
      ),
      2796 => array (
        'name' => 'Indian Rupee',
        'code' => 'INR',
        'symb' => '₹',
      ),
      2797 => array (
        'name' => 'Japanese Yen',
        'code' => 'JPY',
        'symb' => '¥',
      ),
      2798 => array (
        'name' => 'South Korean Won',
        'code' => 'KRW',
        'symb' => '₩',
      ),
      2799 => array (
        'name' => 'Mexican Peso',
        'code' => 'MXN',
        'symb' => '$',
      ),
      2800 => array (
        'name' => 'Malaysian Ringgit',
        'code' => 'MYR',
        'symb' => 'RM',
      ),
      2801 => array (
        'name' => 'Norwegian Krone',
        'code' => 'NOK',
        'symb' => 'kr',
      ),
      2802 => array (
        'name' => 'New Zealand Dollar',
        'code' => 'NZD',
        'symb' => '$',
      ),
      2803 => array (
        'name' => 'Philippine Peso',
        'code' => 'PHP',
        'symb' => '₱',
      ),
      2804 => array (
        'name' => 'Pakistani Rupee',
        'code' => 'PKR',
        'symb' => '₨',
      ),
      2805 => array (
        'name' => 'Polish Złoty',
        'code' => 'PLN',
        'symb' => 'zł',
      ),
      2806 => array (
        'name' => 'Russian Ruble',
        'code' => 'RUB',
        'symb' => '₽',
      ),
      2807 => array (
        'name' => 'Swedish Krona',
        'code' => 'SEK',
        'symb' => 'kr',
      ),
      2808 => array (
        'name' => 'Singapore Dollar',
        'code' => 'SGD',
        'symb' => '$',
      ),
      2809 => array (
        'name' => 'Thai Baht',
        'code' => 'THB',
        'symb' => '฿',
      ),
      2810 => array (
        'name' => 'Turkish Lira',
        'code' => 'TRY',
        'symb' => '₺',
      ),
      2811 => array (
        'name' => 'New Taiwan Dollar',
        'code' => 'TWD',
        'symb' => '$',
      ),
      2812 => array (
        'name' => 'South African Rand',
        'code' => 'ZAR',
        'symb' => 'Rs',
      ),
      2813 => array (
        'name' => 'United Arab Emirates Dirham',
        'code' => 'AED',
        'symb' => 'د.إ',
      ),
      2814 => array (
        'name' => 'Bulgarian Lev',
        'code' => 'BGN',
        'symb' => 'лв',
      ),
      2815 => array (
        'name' => 'Croatian Kuna',
        'code' => 'HRK',
        'symb' => 'kn',
      ),
      2816 => array (
        'name' => 'Mauritian Rupee',
        'code' => 'MUR',
        'symb' => '₨',
      ),
      2817 => array (
        'name' => 'Romanian Leu',
        'code' => 'RON',
        'symb' => 'lei',
      ),
      2818 => array (
        'name' => 'Icelandic Króna',
        'code' => 'ISK',
        'symb' => 'kr',
      ),
      2819 => array (
        'name' => 'Nigerian Naira',
        'code' => 'NGN',
        'symb' => '₦',
      ),
      2820 => array (
        'name' => 'Colombian Peso',
        'code' => 'COP',
        'symb' => '$',
      ),
      2821 => array (
        'name' => 'Argentine Peso',
        'code' => 'ARS',
        'symb' => '$',
      ),
      2822 => array (
        'name' => 'Peruvian Sol',
        'code' => 'PEN',
        'symb' => 'S/.',
      ),
      2823 => array (
        'name' => 'Vietnamese Dong',
        'code' => 'VND',
        'symb' => '₫',
      ),
      2824 => array (
        'name' => 'Ukrainian Hryvnia',
        'code' => 'UAH',
        'symb' => '₴',
      ),
      2832 => array (
        'name' => 'Bolivian Boliviano',
        'code' => 'BOB',
        'symb' => 'Bs.',
      ),
      3526 => array (
        'name' => 'Albanian Lek',
        'code' => 'ALL',
        'symb' => 'L',
      ),
      3527 => array (
        'name' => 'Armenian Dram',
        'code' => 'AMD',
        'symb' => '֏',
      ),
      3528 => array (
        'name' => 'Azerbaijani Manat',
        'code' => 'AZN',
        'symb' => '₼',
      ),
      3529 => array (
        'name' => 'Bosnia-Herzegovina Convertible Mark',
        'code' => 'BAM',
        'symb' => 'KM',
      ),
      3530 => array (
        'name' => 'Bangladeshi Taka',
        'code' => 'BDT',
        'symb' => '৳',
      ),
      3531 => array (
        'name' => 'Bahraini Dinar',
        'code' => 'BHD',
        'symb' => '.د.ب',
      ),
      3532 => array (
        'name' => 'Bermudan Dollar',
        'code' => 'BMD',
        'symb' => '$',
      ),
      3533 => array (
        'name' => 'Belarusian Ruble',
        'code' => 'BYN',
        'symb' => 'Br',
      ),
      3534 => array (
        'name' => 'Costa Rican Colón',
        'code' => 'CRC',
        'symb' => '₡',
      ),
      3535 => array (
        'name' => 'Cuban Peso',
        'code' => 'CUP',
        'symb' => '$',
      ),
      3536 => array (
        'name' => 'Dominican Peso',
        'code' => 'DOP',
        'symb' => '$',
      ),
      3537 => array (
        'name' => 'Algerian Dinar',
        'code' => 'DZD',
        'symb' => 'د.ج',
      ),
      3538 => array (
        'name' => 'Egyptian Pound',
        'code' => 'EGP',
        'symb' => '£',
      ),
      3539 => array (
        'name' => 'Georgian Lari',
        'code' => 'GEL',
        'symb' => '₾',
      ),
      3540 => array (
        'name' => 'Ghanaian Cedi',
        'code' => 'GHS',
        'symb' => '₵',
      ),
      3541 => array (
        'name' => 'Guatemalan Quetzal',
        'code' => 'GTQ',
        'symb' => 'Q',
      ),
      3542 => array (
        'name' => 'Honduran Lempira',
        'code' => 'HNL',
        'symb' => 'L',
      ),
      3543 => array (
        'name' => 'Iraqi Dinar',
        'code' => 'IQD',
        'symb' => 'ع.د',
      ),
      3544 => array (
        'name' => 'Iranian Rial',
        'code' => 'IRR',
        'symb' => '﷼',
      ),
      3545 => array (
        'name' => 'Jamaican Dollar',
        'code' => 'JMD',
        'symb' => '$',
      ),
      3546 => array (
        'name' => 'Jordanian Dinar',
        'code' => 'JOD',
        'symb' => 'د.ا',
      ),
      3547 => array (
        'name' => 'Kenyan Shilling',
        'code' => 'KES',
        'symb' => 'Sh',
      ),
      3548 => array (
        'name' => 'Kyrgystani Som',
        'code' => 'KGS',
        'symb' => 'с',
      ),
      3549 => array (
        'name' => 'Cambodian Riel',
        'code' => 'KHR',
        'symb' => '៛',
      ),
      3550 => array (
        'name' => 'Kuwaiti Dinar',
        'code' => 'KWD',
        'symb' => 'د.ك',
      ),
      3551 => array (
        'name' => 'Kazakhstani Tenge',
        'code' => 'KZT',
        'symb' => '₸',
      ),
      3552 => array (
        'name' => 'Lebanese Pound',
        'code' => 'LBP',
        'symb' => 'ل.ل',
      ),
      3553 => array (
        'name' => 'Sri Lankan Rupee',
        'code' => 'LKR',
        'symb' => 'Rs',
      ),
      3554 => array (
        'name' => 'Moroccan Dirham',
        'code' => 'MAD',
        'symb' => 'د.م.',
      ),
      3555 => array (
        'name' => 'Moldovan Leu',
        'code' => 'MDL',
        'symb' => 'L',
      ),
      3556 => array (
        'name' => 'Macedonian Denar',
        'code' => 'MKD',
        'symb' => 'ден',
      ),
      3557 => array (
        'name' => 'Myanma Kyat',
        'code' => 'MMK',
        'symb' => 'Ks',
      ),
      3558 => array (
        'name' => 'Mongolian Tugrik',
        'code' => 'MNT',
        'symb' => '₮',
      ),
      3559 => array (
        'name' => 'Namibian Dollar',
        'code' => 'NAD',
        'symb' => '$',
      ),
      3560 => array (
        'name' => 'Nicaraguan Córdoba',
        'code' => 'NIO',
        'symb' => 'C$',
      ),
      3561 => array (
        'name' => 'Nepalese Rupee',
        'code' => 'NPR',
        'symb' => '₨',
      ),
      3562 => array (
        'name' => 'Omani Rial',
        'code' => 'OMR',
        'symb' => 'ر.ع.',
      ),
      3563 => array (
        'name' => 'Panamanian Balboa',
        'code' => 'PAB',
        'symb' => 'B/.',
      ),
      3564 => array (
        'name' => 'Qatari Rial',
        'code' => 'QAR',
        'symb' => 'ر.ق',
      ),
      3565 => array (
        'name' => 'Serbian Dinar',
        'code' => 'RSD',
        'symb' => 'дин.',
      ),
      3566 => array (
        'name' => 'Saudi Riyal',
        'code' => 'SAR',
        'symb' => 'ر.س',
      ),
      3567 => array (
        'name' => 'South Sudanese Pound',
        'code' => 'SSP',
        'symb' => '£',
      ),
      3568 => array (
        'name' => 'Tunisian Dinar',
        'code' => 'TND',
        'symb' => 'د.ت',
      ),
      3569 => array (
        'name' => 'Trinidad and Tobago Dollar',
        'code' => 'TTD',
        'symb' => '$',
      ),
      3570 => array (
        'name' => 'Ugandan Shilling',
        'code' => 'UGX',
        'symb' => 'Sh',
      ),
      3571 => array (
        'name' => 'Uruguayan Peso',
        'code' => 'UYU',
        'symb' => '$',
      ),
      3572 => array (
        'name' => 'Uzbekistan Som',
        'code' => 'UZS',
        'symb' => 'so\'m',
      ),
      3573 => array (
        'name' => 'Sovereign Bolivar',
        'code' => 'VES',
        'symb' => 'Bs.',
      ),
    );
    return $fiat_currencies;
  }

  //Get all the Metals supported by Coinmarketcap. 
  public function getAllMetals() {
    $metals = array (
      3575 => array(
        'name' => 'Gold Troy Ounce',
        'code' => 'XAU',
      ),
      3574 => array(
        'name' => 'Silver Troy Ounce',
        'code' => 'XAG',
      ),
      3577 => array(
        'name' => 'Platinum Ounce',
        'code' => 'XPT',
      ),
      3576 => array(
        'name' => 'Palladium Ounce',
        'code' => 'XPD',
      ),                  
    );
    return $metals;
  }
}

?>