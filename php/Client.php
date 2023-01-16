<?php

namespace ImaginaryMachines\UfoAi;

use ImaginaryMachines\UfoAi\Contracts\ClientContract;

/**
 * Interfact with API
 */
class Client  implements ClientContract {

	// url for the api
	protected string $url;
	// api key
	protected string $key;
	// api version
	protected string $version;

	const ROUTE_PROMPT  = '/from-prompt';
	const METHOD_PROMPT = 'POST';
	/**
	 * Constructor
	 *
	 * @param string $url
	 * @param string $key
	 * @param string $version
	 */
	public function __construct( string $url, string $key, string $version = 'v1' ) {
		$this->url     = $url;
		$this->key     = $key;
		$this->version = $version;
	}


	/**
	 * Create instance from saved settings
	 *
	 * @return Client
	 */
	public static function fromSettings(): Client {
		$settings = Settings::getAll();
		if ( isset( $settings[ SETTINGS::KEY ] ) ) {
			$key = $settings[ SETTINGS::KEY ];
		} else {
			$key = Settings::getDefault( Settings::KEY );
		}

		return new Client(
			Settings::getDefault( Settings::URL ),
			$key,
			self::latestApiVersion()
		);
	}

	/**
	 * Check if client is connected with a valid API key
	 */
	public function isConnected():bool {

		$response = wp_remote_get(
			$this->makeUrl( '/user', false ),
			array(
				'method'  => 'GET',
				'timeout' => 10,
				'headers' => $this->getHeaders(),

			)
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		if ( wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return false;
		}
		return true;
	}

	public function text(string $prompt, float $temperature = 0.8):array {
		$this->version = 'v2';

		$response = wp_remote_get(
			$this->makeUrl( '/text', true ),
			array(
				'method'  => 'POST',
				'timeout' => 15,
				'headers' => $this->getHeaders(),
				'body' => json_encode(
					array(
						'prompt' => $prompt,
						'temperature' => $temperature,
					)
				),

			)
		);
		if( is_wp_error( $response ) ){
			return $response;
		}

		return $this->handleResponse( $response );
	}

	/**
	 * Make a prompt request
	 *
	 * @param PromptRequest $promptRequest
	 * @return array
	 */
	public function prompt( PromptRequest $promptRequest ):array {

		$response = wp_remote_post(
			$this->makeUrl( self::ROUTE_PROMPT ),
			array(
				'method'  => self::METHOD_PROMPT,
				//@phpcs:disable
				'timeout' => 15,
				'body'    => json_encode( $promptRequest->toArray() ),
				'headers' => $this->getHeaders(),
			)
		);
		return $this->handleResponse( $response );

	}

	public function edit(string $input, string $instruction){
		$data = [
			'input' => $input,
			'instruction' => $instruction
		];
		$response = wp_remote_post(
			$this->makeUrl( '/text/edit' ),
			array(
				'method'  => 'POST',
				'timeout' => 15,
				'body'    => json_encode( $data ),
				'headers' => $this->getHeaders(),
			)
		);
		return $this->handleResponse( $response );

	}

	protected function handleResponse($response){
		// check if is_wp_error
		if ( is_wp_error( $response ) ) {
			throw new \Exception( $response->get_error_message() );
		}
		// Check if status is 201 with wp_remote_retrieve_response_code
		if ( wp_remote_retrieve_response_code( $response ) !== 201 ) {
			$message  = 'Response failed with ' . wp_remote_retrieve_response_code( $response );
			$message .= '. ' . wp_remote_retrieve_response_message( $response );
			throw new \Exception( $message );
		}
		// decode the body with json_decode
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		// check if body is array with key "texts"
		if ( ! is_array( $body ) || ! array_key_exists( 'texts', $body ) ) {
			throw new \Exception( 'Invalid ufo-ai response body' );
		}
		// Ensure each key of "texts" is a string
		foreach ( $body['texts'] as $key => $text ) {
			if ( ! is_string( $text ) ) {
				throw new \Exception(
					sprintf( 'Invalid ufo-ai response body, key of texts %s is not a string', $key )
				);

			}
		}
		// return the texts
		return $body['texts'];
	}

	/**
	 * Get latest api version
	 *
	 * @return string
	 */
	public static function latestApiVersion():string {
		return 'v1';
	}


	/**
	 * Get API key
	 *
	 * @return string
	 */
	public function getKey(): string {
		return $this->key;
	}

	/**
	 *  Get api url
	 *
	 * @return string
	 */
	public function getUrl(): string {
		return $this->url;
	}


	/**
	 * Make a url
	 *
	 * @param string $endpoint
	 * @param bool $withVersion
	 * @return string
	 */
	protected function makeUrl( string $endpoint, bool $withVersion = true ):string {
		$url = $this->url;
		if ( $withVersion ) {
			$url .= 'api/' . $this->version;
		}else{
			$url .= 'api';
		}
		$url .= $endpoint;
		return $url;
	}

	/**
	 * Array of headers for requests
	 *
	 * @return array
	 */
	protected function getHeaders():array {
		return [
			// content type json
			'Content-Type'  => 'application/json; charset=utf-8',
			'Accept'        => 'application/json',
			// api key as bearer token
			'Authorization' => 'Bearer ' . $this->key,
			'Accept'        => 'application/json',
		];
	}

}
