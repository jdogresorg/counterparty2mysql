<?php
/*********************************************************************
 * counterparty-v2-api.php - Counterparty v2 API Interface
 ********************************************************************/

class CounterpartyV2API {

    // Aliases for CURL info
    private $curl;
    private $timeout;
    private $url;
    public  $status;

    // Setup CURL request parameters
    function __construct(){
    	// Set defaults
    	$this->url     = ''; // Empty URL
    	$this->timeout = 30; // 30 seconds
    	// Initialize the Curl request handler
    	$this->init();
    }

    // Handle initializing curl request handler
    function init(){
	    $this->curl = curl_init();
	    curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
	    curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($this->curl, CURLOPT_FAILONERROR,    true);	    
	    $this->setTimeout($this->timeout);
    }

    // Handle setting CURL request url
    function setUrl( $url ){
    	$this->url = $url;
	    curl_setopt($this->curl, CURLOPT_URL, $url);
    }

    // Handle setting CURL timeout (in seconds)
    function setTimeout( $timeout ){
    	$this->timeout = $timeout;
	    curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
    }

    // Handle making an API request
    // TODO: Handle adding `cursor` support to request all data recursively (only needed if not able to get all data in a single request)
    function request($url, $timeout=NULL){
    	// print "making request to {$url}...\n";
    	$curl = $this->curl;
    	if(isset($timeout) && $timeout != $this->timeout)
    		$this->setTimeout($timeout);
    	if(isset($url) && $url != $this->url)
    		$this->setUrl($url);
    	// Execute the curl request
    	$response=curl_exec($curl);
    	// Detect any curl error
		if(curl_errno($curl)){
		    $error = curl_error($curl);
            byeLog('CURL Error : ' . $error);
		} else {
			// Decode the json
			$data = json_decode($response);
			if(isset($data) && isset($data->result)){
				return $data->result;
			} else {
				$error = ($data->error) ? $data->error : 'Error getting Counterparty data';
				byeLog('Request Error :' . $error);
			}
		}    	
    }

    // Handle getting API status
    function getStatus(){
    	$url  = CP_HOST . '/v2/';
    	$data = $this->request($url);
    	$this->status = $data;
    }

    // Handle getting block events
    function getBlockEvents( $block_index ){
    	$url  = CP_HOST . '/v2/blocks/' . $block_index . '/events?verbose=1&limit=1000000';
    	$data = $this->request($url);
    	// Reverse the order of events since the API hands back in descending order
    	$data = array_reverse($data);
    	// Store block_index and timestamp in every event (needed when creating messages)
    	foreach($data as $idx => $info){
    		if($info->event=='NEW_BLOCK'){
    			$block_index = $info->params->block_index;
    			$block_time  = $info->params->block_time;
    		}
    		$info->block_index = $block_index;
    		$info->block_time  = $block_time;
    		$data[$idx] = $info;
    	}
    	return $data;
    }

    // Handle getting asset information
    function getMessages( $block_index ){
		$events = $this->getBlockEvents($block_index);
		$messages = array();
	    foreach($events as $event){
        	$message = $this->eventToMessage($event);
        	array_push($messages, $message);
		}
    	return $messages;
    }

    // Handle getting address balances
    function getAddressBalances( $address ){
    	$url  = CP_HOST . '/v2/addresses/' . $address . '/balances?limit=1000000';
    	$data = $this->request($url);
    	return $data;
    }

    // Handle getting asset information
    function getAssetInfo( $asset ){
    	$url  = CP_HOST . '/v2/assets/' . $asset;
    	$data = $this->request($url);
    	return $data;
    }



	// Handle creating message based on event
	// Note: This is because the counterparty-core devs broke the /v1/ get_messages API endpoint and it no longer returns all messages as expected
	//       as a result we need to now request all events from the /v2/ API and convert them into messages which can then be used in the messages table
	function eventToMessage( $event ){
		$message = (object) [];
		$message->message_index = $event->event_index;
		$message->block_index   = $event->block_index;
		$message->timestamp     = $event->block_time;
		$message->event         = $event->event;
		$message->tx_hash       = $event->tx_hash;
		// Handle setting the command based off event (command = database action)
		$message->command       = 'insert';
		if(str_contains($event->event,'_UPDATE')||str_contains($event->event,'_FILLED'))
			$message->command   = 'update';
		if(str_contains($event->event,'_PARSED'))
			$message->command   = 'parse';
		// Handle setting category based off event (category = database table name)
		switch($event->event){
			case 'NEW_BLOCK':
			case 'BLOCK_PARSED':
				$message->category = 'blocks';
				break;
			case 'NEW_TRANSACTION':
			case 'TRANSACTION_PARSED':
				$message->category = 'transactions';
				break;
			case 'NEW_TRANSACTION_OUTPUT':
				$message->category = 'transaction_outputs';
				break;
			// Sends
			case 'SEND':
			case 'ENHANCED_SEND':
			case 'MPMA_SEND':
			case 'ATTACH_TO_UTXO':
			case 'DETACH_FROM_UTXO':
			case 'UTXO_MOVE':
				$message->category = 'sends';
				break;
			// issuances
			case 'ASSET_ISSUANCE':
			case 'ASSET_TRANSFER':
			case 'RESET_ISSUANCE':
				$message->category = 'issuances';
				break;
			case 'ASSET_CREATION':
				$message->category = 'assets';
				break;
			case 'ASSET_DIVIDEND':
				$message->category = 'dividends';
				break;
			case 'ASSET_DESTRUCTION':
				$message->category = 'destructions';
				break;
			// Orders
			case 'OPEN_ORDER':
			case 'ORDER_UPDATE':
			case 'ORDER_FILLED':
				$message->category = 'orders';
				break;
			case 'ORDER_MATCH':
			case 'ORDER_MATCH_UPDATE':
				$message->category = 'order_matches';
				break;
			case 'CANCEL_BET':
			case 'CANCEL_ORDER':
			case 'INVALID_CANCEL':
				$message->category = 'cancels';
				break;
			case 'BTC_PAY':
				$message->category = 'btcpays';
				break;
			case 'ORDER_EXPIRATION':
				$message->category = 'order_expirations';
				break;
			case 'ORDER_MATCH_EXPIRATION':
				$message->category = 'order_match_expirations';
				break;
			case 'OPEN_DISPENSER':
			case 'DISPENSER_UPDATE':
				$message->category = 'dispensers';
				break;
			case 'REFILL_DISPENSER':
				$message->category = 'dispenser_refills';
				break;
			case 'NEW_FAIRMINTER':
			case 'FAIRMINTER_UPDATE':
				$message->category = 'fairminters';
				break;
			case 'NEW_FAIRMINT':
				$message->category = 'fairmints';
				break;
			case 'BET_MATCH':
			case 'BET_MATCH_UPDATE':
				$message->category = 'bet_matches';
				break;
			case 'OPEN_BET':
			case 'BET_UPDATE':
				$message->category = 'bets';
				break;
			// core devs dunno how to spell... lol
			case 'BET_MATCH_RESOLUTON':
			case 'BET_MATCH_RESOLUTION':
				$message->category = 'bet_match_resolutions';
				break;
			case 'DISPENSE':
				$message->category = 'dispenses';
				break;
			case 'INCREMENT_TRANSACTION_COUNT':
				$message->category = 'transaction_count';
				break;
			case 'NEW_ADDRESS_OPTIONS':
				$message->category = 'addresses';
				break;
			// Rock Paper Scissors (RPS)
			case 'OPEN_RPS':
			case 'RPS_UPDATE':
				$message->category = 'rps';
				break;
			case 'RPS_MATCH':
			case 'RPS_MATCH_UPDATE':
				$message->category = 'rps_matches';
				break;
			case 'RPS_RESOLVE':
				$message->category = 'rpsresolves';
				break;
			// Defaults
			case 'BET_EXPIRATION':
			case 'BET_MATCH_EXPIRATION':
			case 'BURN':
			case 'BROADCAST':
			case 'CREDIT':
			case 'DEBIT':
			case 'DISPENSE':
			case 'RPS_EXPIRATION':
			case 'RPS_MATCH_EXPIRATION':
			case 'SWEEP':
			default:
				$message->category = strtolower($event->event . 's');
		}
		// Cleanup bindings data by removing extra values
		// unset($event->params->asset_info);
		// unset($event->params->get_asset_info);
		// unset($event->params->give_asset_info);
		// unset($event->params->forward_asset_info);
		// unset($event->params->backward_asset_info);
		// Remove all 'normalized' values
		foreach($event->params as $key => $value){
			if(str_contains($key, '_normalized'))
				unset($event->params->{$key});
			if(str_contains($key, '_info'))
				unset($event->params->{$key});
		}
		if($event->event != 'NEW_BLOCK')
			unset($event->params->block_time);
		// Encode the bindings as a JSON string
		$message->bindings = json_encode($event->params);
	    return $message;
	}

}