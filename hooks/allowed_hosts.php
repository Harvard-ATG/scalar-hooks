<?php

/**
 * Scalar_hook_allowed_hosts class
 * 
 * This class implements a method to check the value of the HTTP HOST header
 * and deny access if it is not an allowed hostname or subdomain.
 * 
 * The hostname is checked in two ways:
 * 
 *  1) The SCALAR_ALLOWED_HOSTS environment variable is consulted, which should
 *     contain a list of valid hostnames (comma-separated).
 *  2) If the host is determined to be a subdomain, based on the SCALAR_DOMAIN
 *     environment variable or the default SERVER_NAME, then it is only allowed
 *     after being validated against a book of the same name. The assumption is
 *     that the book slug will be used as the subdomain.
 * 
 * Configure in _config/hooks.php_ as follows:
 * 
 *   $hook['post_controller_constructor'] = array(
 *      'class'    => 'Scalar_hook_allowed_hosts',
 *      'function' => 'process_request',
 *      'filename' => 'allowed_hosts.php',
 *      'filepath' => 'hooks',
 *      'params'   => array('subdomains' => true)
 *   );
 * 
 * Environment variables:

 * - SCALAR_DOMAIN : domain name used to check for subdomains
 * - SCALAR_ALLOWED_HOSTS : string of comma separated hosts
 * - SCALAR_DEBUG : when enabled and allowed hosts is empty, adds localhost automatically
 * 
 */
class Scalar_hook_allowed_hosts {
    public $CI = null;  // holds codeigniter instance
    public $params = array('subdomains' => false); // holds hook parameters

		public function __construct() {}

    public function init($params) {
        if(isset($params['subdomains'])) {
          $this->params['subdomains'] = (bool) $params['subdomains'];
        }
        if(!isset($this->CI)) {
          $this->CI =& get_instance();
          $this->CI->load->database();
        }
    }

    public function process_request($params) {
        $this->init($params);
        $host = $this->get_requested_host();
        if(!$this->allowed_host($host)) {
            error_log("Scalar_hook_allowed_hosts returned 403 for requested host=$host");
            show_error("Access denied: ".$host, 403, "Forbidden");
        }
    }

    public function get_requested_host() {
        return strtolower(isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : $_SERVER['HTTP_HOST']);
    }

    public function get_scalar_domain() {
        return strtolower(getenv('SCALAR_DOMAIN') ? getenv('SCALAR_DOMAIN') : $_SERVER['SERVER_NAME']);
    }

    public function debug_enabled() {
        return getenv('SCALAR_DEBUG') ? (bool) getenv('SCALAR_DEBUG') : false;
    }

    public function whitelist() {
        $whitelist = strtolower(getenv('SCALAR_ALLOWED_HOSTS') ? getenv('SCALAR_ALLOWED_HOSTS') : '');
        $whitelist = explode(",", $whitelist);
        $whitelist = array_filter(array_map(function($host) {
            return trim($host);
        }, $whitelist));

        if(empty($whitelist) && $this->debug_enabled()) {
            $whitelist = array("127.0.0.1", "localhost", "[::1]");
        }
        return $whitelist;
    }

		public function is_allowed_host($host) {
			$is_allowed = $this->is_whitelisted($host);
			if($this->params['subdomains']) {
					$is_allowed = $is_allowed || $this->is_allowed_subdomain();
			}
			return $is_allowed;
		}

    public function is_whitelisted($host) {
        foreach($this->whitelist() as $item) {
            if($item == "*" || $item == $host) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function is_allowed_subdomain($host) {
        $domain = $this->get_scalar_domain();
        $subdomain = $this->get_subdomain_from_host($host, $domain);
        if($subdomain !== FALSE) {
            if($this->is_valid_book($subdomain)) {
                return TRUE;
            }
        }
        return FALSE;
    }

    public function get_subdomain_from_host($host, $domain) {
        if(strpos($host, $domain) === FALSE) {
            return FALSE; // not a subdomain unless hostname contains the domain
        }
        $subdomain = substr($host, 0, strlen($host) - strlen($domain));
        if(substr($subdomain, -1) !== ".") {
            return FALSE; // not a subdomain unless it is dot-separated from the domain
        }
        return substr($subdomain, 0, -1); // return the subdomain without the dot at the end
    }

    public function is_valid_book($book_slug) {
        $sql = 'SELECT slug FROM scalar_db_books WHERE slug = ?';
        $query = $this->CI->db->query($sql, array($book_slug));
        $result = $query->result(); 
        return sizeof($result) == 1;
    }
}

