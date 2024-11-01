<?php

class BootpayApi {
	/**
	 * Container for the objects.
	 *
	 * @since   0.1
	 */
	private static $instances = null;

	/**
	 * Get an instance of the current, called class.
	 *
	 * @since   0.1
	 * @access  public
	 * @return  object An instance of $cls
	 */
	public static function instance() {
		! isset( static::$instances ) && self::$instances = new static;

		return static::$instances;
	}

	private $defaultParams = [];

	const BASE_URL       = 'https://api.bootpay.co.kr/';
	const ANALYTICS      = 'https://analytics.bootpay.co.rk/';
	const URL_CONFIRM    = self::BASE_URL . 'receipt/';
	const URL_CANCEL     = self::BASE_URL . 'cancel';
	const URL_LOGIN      = self::ANALYTICS . 'login';
	const DEFAULT_FORMAT = 'json';

	public static function setConfig( $applicationId, $privateKey ) {
		static::instance();
		static::$instances->defaultParams = [
			'application_id' => $applicationId,
			'private_key'    => $privateKey,
			'format'         => self::DEFAULT_FORMAT
		];

		return static::$instances;
	}

	public static function confirm( $data ) {
		$payload = array_merge( $data, static::$instances->defaultParams );

		return static::$instances->confirmInstance( $payload );
	}

	public static function cancel( $data ) {
		$payload = array_merge( $data, static::$instances->defaultParams );

		return static::$instances->cancelInstance( $payload );
	}

	public static function login( $data ) {
		$payload = array_merge( $data, static::$instances->defaultParams );

		return static::$instances->loginInstance( $payload );
	}

	public function cancelInstance( $data ) {
		return self::post( self::URL_CANCEL, $data );
	}

	public function confirmInstance( $data ) {
		return self::get( self::URL_CONFIRM . $data['receipt_id'], $data );
	}

	public function loginInstance( $data ) {
		return self::post( self::URL_LOGIN, $data );
	}

//  공통 부분
	public static function get( $url, $data ) {
		$url  = $url . '?' . http_build_query( $data );
		$body = wp_remote_retrieve_body( wp_remote_get( $url ) );

		return json_decode( trim( $body ) );
	}

	public static function post( $url, $data ) {
		$body = wp_remote_retrieve_body( wp_remote_post( $url, ['body' => $data] ) );

		return json_decode( trim( $body ) );
	}
}