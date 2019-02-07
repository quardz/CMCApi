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
}

?>