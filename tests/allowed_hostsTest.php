<?php

use PHPUnit\Framework\TestCase;


class Scalar_hook_allowed_hostsTest extends TestCase {
    public function test_get_scalar_domain() {
        global $_SERVER;
        $_SERVER['SERVER_NAME'] = 'bar.scalar.org';
        $scalar_domain = 'foo.scalar.org';
        $hook = new Scalar_hook_allowed_hosts();

        putenv("SCALAR_DOMAIN=$scalar_domain");
                $this->assertEquals($scalar_domain, getenv('SCALAR_DOMAIN'));
        $this->assertEquals($scalar_domain, $hook->get_scalar_domain());

        putenv("SCALAR_DOMAIN="); // sets env var to empty value
        $this->assertEquals($_SERVER['SERVER_NAME'], $hook->get_scalar_domain());

        putenv("SCALAR_DOMAIN"); // unsets env var 
        $this->assertEquals($_SERVER['SERVER_NAME'], $hook->get_scalar_domain());
    }

    public function test_whitelist() {
        $hook = new Scalar_hook_allowed_hosts();
        putenv("SCALAR_ALLOWED_HOSTS=");
        $this->assertEquals(array(), $hook->whitelist());

        putenv("SCALAR_ALLOWED_HOSTS=foo.scalar.org");
        $this->assertEquals(array('foo.scalar.org'), $hook->whitelist());

        $allowed_hosts = '10.0.1.182,34.56.78.90,foo.scalar.org';
        putenv("SCALAR_ALLOWED_HOSTS=$allowed_hosts");
        $this->assertEquals(explode(',', $allowed_hosts), $hook->whitelist());

        $allowed_hosts = '  foo.scalar.org,   www.scalar.org   ';
        putenv("SCALAR_ALLOWED_HOSTS=$allowed_hosts");
        $this->assertEquals(array('foo.scalar.org', 'www.scalar.org'), $hook->whitelist());

				putenv("SCALAR_ALLOWED_HOSTS=");
				putenv("SCALAR_DEBUG=1");
				$this->assertEquals(array("127.0.0.1", "localhost", "[::1]"), $hook->whitelist());

    }

    public function test_host_is_whitelisted() {
        $hook = new Scalar_hook_allowed_hosts();
        $allowed_host = 'foo.scalar.org';
        putenv("SCALAR_ALLOWED_HOSTS=$allowed_host");
        $this->assertFalse($hook->is_whitelisted("not$allowed_host"));
        $this->assertTrue($hook->is_whitelisted($allowed_host));
    }

    public function test_wildcard_is_whitelisted() {
        $hook = new Scalar_hook_allowed_hosts();
        putenv("SCALAR_ALLOWED_HOSTS=*");
        $test_hosts = array("scalar.org","abc.scalar.org","10.0.1.182", "34.56.78.90", "");
        foreach($test_hosts as $host) {
            $this->assertTrue($hook->is_whitelisted($host));
        }
    }

    public function test_get_subdomain_from_host() {
        $hook = new Scalar_hook_allowed_hosts();
        $tests = array(
            array(
              'host' => 'foo.scalar.org', 
              'domain' => 'scalar.org', 
              'expected' => 'foo'
            ), 
            array(
              'host' => 'bar.scalar.org', 
              'domain' => 'scalar.org', 
              'expected' => 'bar'
            ),
            array(
              'host' => 'scalar.org', 
              'domain' => 'scalar.org', 
              'expected' => FALSE
            ),
            array(
              'host' => 'scalar.org', 
              'domain' => 'foo.scalar.org', 
              'expected' => FALSE
            ),
        );
        foreach($tests as $test) {
            $this->assertEquals($test['expected'], $hook->get_subdomain_from_host($test['host'], $test['domain']));
        }
    }

    public function test_is_allowed_subdomain() {
        $hook = $this->getMockBuilder('Scalar_hook_allowed_hosts')
            ->setMethods(array('get_scalar_domain', 'is_valid_book'))
            ->getMock();

        $domain = 'scalar.org';
        $subdomain = 'foo';

        $hook->method('get_scalar_domain')->willReturn($domain);
        $hook->method('is_valid_book')->willReturn(true);
        $this->assertTrue($hook->is_allowed_subdomain("{$subdomain}.{$domain}"));
    }

    public function test_is_allowed_host_when_subdomain_allowed() {
        $host = "foo.scalar.org";
        $scenarios = array(
            array(
                'subdomain_allowed' => true, 
                'tests' => array(
                    array('is_whitelisted' => false, 'is_allowed_subdomain' => false, 'expected' => false),
                    array('is_whitelisted' => false, 'is_allowed_subdomain' => true, 'expected' => true),
                    array('is_whitelisted' => true, 'is_allowed_subdomain' => false, 'expected' => true),
                    array('is_whitelisted' => true, 'is_allowed_subdomain' => true, 'expected' => true)
                )
            ),
            array(
                'subdomain_allowed' => false, 
                'tests' => array(
                    array('is_whitelisted' => false, 'is_allowed_subdomain' => false, 'expected' => false),
                    array('is_whitelisted' => false, 'is_allowed_subdomain' => true, 'expected' => false),
                    array('is_whitelisted' => true, 'is_allowed_subdomain' => false, 'expected' => true),
                    array('is_whitelisted' => true, 'is_allowed_subdomain' => true, 'expected' => true)
                )
            )
        );

        foreach($scenarios as $scenario) {
            foreach($scenario['tests'] as $test) {
                $hook = $this->getMockBuilder('Scalar_hook_allowed_hosts')
                    ->setMethods(array('is_allowed_subdomain','is_whitelisted'))
                    ->getMock();
                $hook->params['subdomain_allowed'] = $scenario['subdomain_allowed'];
                $hook->method('is_whitelisted')->willReturn($test['is_whitelisted']);
                $hook->method('is_allowed_subdomain')->willReturn($test['is_allowed_subdomain']);

                $this->assertEquals($test['expected'], $hook->is_allowed_host($host));
            }
        }
    }
}
