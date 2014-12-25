<?php

class imdbPlugin extends basePlugin {

	private $disabled;

	/**
	 * Called when plugins are loaded
	 *
	 * @param mixed[]	$config
	 * @param resource 	$socket
	**/
	public function __construct($config, $socket) {
		parent::__construct($config, $socket);
		$this->disabled = false;
		if (!ini_get('allow_url_fopen')) {
			try {
				ini_set('allow_url_fopen', '1');
			} catch (Exception $e) {
				logMsg("Unable to enable allow_url_fopen, disabling imdbPlugin.");
				$this->disabled = true;
			}
		}
	}

	/**
	 * @return array
	 */
	public function help() {
		return array(
			array(
				'command'     => 'imdbid <query>',
				'description' => 'Searches the IMDB for <query> and returns one or more IMDB IDs.'
			)
		);
	}

	/**
	 * Called when messages are posted on the channel
	 * the bot are in, or when somebody talks to it
	 *
	 * @param string $from
	 * @param string $channel
	 * @param string $msg
	 */
	public function onMessage($from, $channel, $msg) {
		if ($this->disabled === true) {
			return;
		}
		if(stringStartsWith(strtolower($msg), $this->config['trigger'] . "imdbid")) {
			$query = trim(str_replace("{$this->config['trigger']}imdbid", "", $msg));
			if (!empty($query)) {
				$query = ucwords($query);
				if (($data = $this->imdbSearch($query)) !== false && isset($data["title_popular"])) {
					$lines = count($data["title_popular"]);
					if ($lines == 1) {
						$title = $data["title_popular"][0];
						sendMessage($this->socket, $channel, "{$from}: IMDB ID of \"" . $title["title"] . "\" is " . $title["id"]);
					} else {
						if ($channel != $from) {
							sendMessage($this->socket, $channel, "Found " . $lines . " entries for \"" . $query . "\", I've send you a message.");
						} else {
							sendMessage($this->socket, $channel, "Found " . $lines . " entries for \"" . $query . "\":");
						}
						foreach ($data["title_popular"] as $title) {
							sendMessage($this->socket, $from, "IMDB ID of \"" . $title["title"] . "\" is " . $title["id"]);
						}
					}
				} else {
					sendMessage($this->socket, $channel, "{$from}: Nothing found for \"" . $query . "\"!");
				}
			}
		}
	}

	private function imdbSearch($query) {
		$data = file_get_contents("http://www.imdb.com/xml/find?json=1&tt=on&nm=on&q=" . rawurlencode($query));
		if (!empty($data) && ($data = json_decode($data, true)) !== NULL) {
			return $data;
		} else {
			return false;
		}
	}
}