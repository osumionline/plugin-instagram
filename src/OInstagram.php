<?php declare(strict_types=1);

namespace Osumi\OsumiFramework\Plugins;

use Osumi\OsumiFramework\Tools\OTools;

/**
 * Utility class with tools to call Instagram's graph API
 */
class OInstagram {
	private string $instagram_api_url = 'https://api.instagram.com/oauth/';
	private string $instagram_graph_url = 'https://graph.instagram.com/';
	private string $client_id = '';
	private string $client_secret = '';
	private string $redirect_uri = '';
	private string $short_lived_access_token = '';
	private string $long_lived_access_token = '';
	private int $long_lived_access_token_expires_in = 0;
	private int $long_lived_access_token_expires_when = 0;

	public function __construct(string $client_id = '', string $client_secret = '') {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
	}

	/**
	 * Sets the redirect URI used on authentication
	 *
	 * @param string $redirect_uri URI where Instagram will redirect after authentication success/failure
	 *
	 * @return void
	 */
	public function setRedirectUri(string $redirect_uri): void {
		$this->redirect_uri = $redirect_uri;
	}

	/**
	 * Returns the redirect URI previously set
	 *
	 * @return string Redirect URI previousely set
	 */
	public function getRedirectUri(): string {
		return $this->redirect_uri;
	}

	/**
	 * Sets the short lived (1 hour) access token from Instagram
	 *
	 * @param string $short_lived_access_token Short lived access token
	 *
	 * @return void
	 */
	public function setShortLivedAccessToken(string $short_lived_access_token): void {
		$this->short_lived_access_token = $short_lived_access_token;
	}

	/**
	 * Returns the short lived (1 hour) access token from Instagram
	 *
	 * @return string Short lived access token
	 */
	public function getShortLivedAccessToken(): string {
		return $this->short_lived_access_token;
	}

	/**
	 * Sets the long lived (60 days) access token from Instagram
	 *
	 * @param string $long_live_access_token Long lived access token
	 *
	 * @return void
	 */
	public function setLongLivedAccessToken(string $long_lived_access_token): void {
		$this->long_lived_access_token = $long_lived_access_token;
	}

	/**
	 * Returns the long lived (60 days) access token from Instagram
	 *
	 * @return string Long lived access token
	 */
	public function getLongLivedAccessToken(): string {
		return $this->long_lived_access_token;
	}

	/**
	 * Sets the expiration time for the long lived (60 days) access token from Instagram
	 *
	 * @param int $long_live_access_token_expires_in Time until the access token expires
	 *
	 * @return void
	 */
	public function setLongLivedAccessTokenExpiresIn(int $long_live_access_token_expires_in): void {
		$this->long_lived_access_token_expires_in = $long_live_access_token_expires_in;
	}

	/**
	 * Returns the expiration time for the long lived (60 days) access token from Instagram
	 *
	 * @return int Long lived access token
	 */
	public function getLongLivedAccessTokenExpiresIn(): int {
		return $this->long_lived_access_token_expires_in;
	}

	/**
	 * Sets the expiration date (timestamp) for the long lived access token from Instagram
	 *
	 * @param int $long_live_access_token_expires_when Date (timestamp) the access token expires
	 *
	 * @return void
	 */
	public function setLongLivedAccessTokenExpiresWhen(int $long_live_access_token_expires_when): void {
		$this->long_lived_access_token_expires_when = $long_live_access_token_expires_when;
	}

	/**
	 * Returns the expiration date (timestamp) for the long lived access token from Instagram
	 *
	 * @return int Date (timestamp) the access token expires
	 */
	public function getLongLivedAccessTokenExpiresWhen(): int {
		return $this->long_lived_access_token_expires_when;
	}

	/**
	 * Gets if the long lived access token is expired
	 *
	 * @return bool Returns if the long lived access token is expired
	 */
	public function isLongLivedAccessTokenExpired(): bool {
		return (
			$this->getLongLivedAccessToken() != '' &&
			$this->getLongLivedAccessTokenExpiresIn() != 0 &&
			time() > $this->getLongLivedAccessTokenExpiresWhen()
		);
	}

	/**
	 * Get the URL where a user has to be sent in order to get an access_token
	 *
	 * @param string $redirect_uri The URI the user will be sent back after login in and granting permissions. Or after declining it.
	 *
	 * @param array $scope List of permissions asked to the user. Defaults to user's basic info and media.
	 *
	 * @return string URL the user has to be redirected to.
	 */
	public function getAuthorizeUrl(string $redirect_uri, array $scope = []): string {
		$this->setRedirectUri($redirect_uri);
		if (count($scope)==0) {
			$scope = ['user_profile', 'user_media'];
		}

		$url = $this->instagram_api_url.'authorize?client_id='.$this->client_id;
		$url .= '&redirect_uri='.$redirect_uri;
		$url .= '&scope='.implode(',', $scope);
		$url .= '&response_type=code';

		return $url;
	}

	/**
	 * Create a short lived access token based on Instagram's return code
	 *
	 * @param string $code Code returned from Instagram on user authentication
	 *
	 * @return array Access token returned by Instagram or null if something fails
	 */
	public function createShotLivedAccessToken(string $code): ?array {
		$short_lived_access_token = OTools::curlRequest('post', $this->instagram_api_url.'access_token', [
			'client_id' => $this->client_id,
			'client_secret' => $this->client_secret,
			'grant_type' => 'authorization_code',
			'redirect_uri' => $this->getRedirectUri(),
			'code' => $code
		]);

		$short_parsed = json_decode($short_lived_access_token, true);

		// If the result is not null, then there is an access token
		if (!is_null($short_parsed)) {
			$this->setShortLivedAccessToken($short_parsed['access_token']);
		}

		return $short_parsed;
	}

	/**
	 * Create a long lived access token based on a short lived access token previously generated
	 *
	 * @return array Access token returned by Instagram or null if something fails
	 */
	public function createLongLivedAccessToken(): ?array {
		$long_lived_access_token = OTools::curlRequest('get', $this->instagram_graph_url.'access_token', [
			'grant_type' => 'ig_exchange_token',
			'client_secret' => $this->client_secret,
			'access_token' => $this->getShortLivedAccessToken()
		]);

		$long_parsed = json_decode($long_lived_access_token, true);

		// If the result is not null, then there is an access token
		if (!is_null($long_parsed)) {
			$this->setLongLivedAccessToken($long_parsed['access_token']);
			$this->setLongLivedAccessTokenExpiresIn($long_parsed['expires_in']);
		}

		return $long_parsed;
	}

	/**
	 * Get personal media with the required fields. Defaults to using the long lived access token if present and not expired,
	 * otherwise uses the short lived access token
	 *
	 * @param array $fields Fields asked to Instagram on each media element
	 *
	 * @param int $limit
	 */
	public function getMeMedia(array $fields = [], int $limit = null): ?array {
		if (count($fields)==0) {
			$fields = ['caption', 'id', 'media_type', 'media_url', 'permalink', 'thumbnail_url', 'timestamp'];
		}
		$params = [
			'fields' => implode(',', $fields),
			'access_token' => ($this->isLongLivedAccessTokenExpired() ? $this->getShortLivedAccessToken() : $this->getLongLivedAccessToken())
		];
		if (!is_null($limit)) {
			$params['limit'] = $limit;
		}
		$posts = OTools::curlRequest('get', $this->instagram_graph_url.'me/media', $params);
		$posts_parsed = json_decode($posts, true);

		return $posts_parsed;
	}

	/**
	 * Refresh long lived access token before it expires
	 *
	 * @return array Access token returned by Instagram or null if something fails
	 */
	public function refreshLongLivedAccessToken(): ?array {
		$long_lived_access_token = OTools::curlRequest('get', $this->instagram_graph_url.'refresh_access_token', [
			'grant_type' => 'ig_refresh_token',
			'access_token' => $this->getLongLivedAccessToken()
		]);

		$long_parsed = json_decode($long_lived_access_token, true);

		// If the result is not null, then there is an access token
		if (!is_null($long_parsed)) {
			$this->setLongLivedAccessToken($long_parsed['access_token']);
			$this->setLongLivedAccessTokenExpiresIn($long_parsed['expires_in']);
		}

		return $long_parsed;
	}
}
