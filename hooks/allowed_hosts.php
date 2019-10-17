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
 */
class Scalar_hook_allowed_hosts {
    public $CI = null;  // holds codeigniter instance
    public $params = array('subdomain_allowed' => false); // holds hook parameters
    public $default_subdomain_validator = 'is_valid_book';

	public function __construct() {}

    public function init($params) {
        if(isset($params['subdomain_allowed'])) {
          $this->params['subdomain_allowed'] = (bool) $params['subdomain_allowed'];
        }
        if(!isset($this->CI)) {
          $this->CI =& get_instance();
          $this->CI->load->database();
        }
    }

    public function process_request($params) {
        $this->init($params);
        $host = $this->get_requested_host();
        if(!$this->is_allowed_host($host)) {
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
        if($this->params['subdomain_allowed']) {
            $is_allowed = $is_allowed || $this->is_allowed_subdomain($host);
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
        $subdomain_validator = isset($this->params['subdomain_validator']) ? $this->params['subdomain_validator'] : $this->default_subdomain_validator;
        if(!is_callable(array($this, $subdomain_validator))) {
            throw new Exception("Invalid subdomain_validator '$subdomain_validator'");
        }
        if($subdomain !== FALSE) {
            return $this->$subdomain_validator($subdomain);
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

    public function is_always_true($book_slug) {
        return TRUE;
    }

    public function is_valid_book($book_slug) {
        $sql = 'SELECT slug FROM scalar_db_books WHERE slug = ?';
        $query = $this->CI->db->query($sql, array($book_slug));
        return sizeof($query->result()) == 1;
    }

    public function is_valid_book_and_subdomain_is_on($book_slug) {
        $sql = 'SELECT slug FROM scalar_db_books WHERE slug = ? and subdomain_is_on = 1';
        $query = $this->CI->db->query($sql, array($book_slug));
        return sizeof($query->result()) == 1;
    }
}
