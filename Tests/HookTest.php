<?php
/**
 * PHP unit test
 */
namespace Pentagonal\Hookable\TestCase;

use Pentagonal\Hookable\Hookable;
use PHPUnit_Framework_TestCase;

class HookTest extends PHPUnit_Framework_TestCase
{
    protected $hook;

    protected $asserted_string = 'asserted string';
    protected $asserted_string_overriden = 'asserted string overriden';
    protected $asserted_string_callback_name = 'asserted_fn_string_cb';

    /**
     * HookTest constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->hook = new Hookable();
    }

    /**
     * Function assert String return valu
     *
     * @return mixed
     */
    public function callbackAssertedAsString()
    {
        $asserted_string = $this->asserted_string;
        // appling
        return $this->hook->apply(
            $this->asserted_string_callback_name,
            $asserted_string
        );
    }

    /**
     * Callback for Override Hook
     *
     * @return string
     */
    public function callbackAssertedAsStringOverrideHook()
    {
        return $this->asserted_string_overriden;
    }

    /**
     * Assert Test
     *
     * @return void
     * @throws Exception
     */
    public function testAssertEqualities()
    {
        /**
         * Asserting Equals
         */
        $this->assertEquals(
            $this->asserted_string,
            $this->callbackAssertedAsString()
        );

        /**
         * Add action Hooks
         * @priority 90
         */
        $this->hook->add(
            $this->asserted_string_callback_name,
            array($this, 'callbackAssertedAsStringOverrideHook'),
            90
        );

        // re^check result function
        $this->assertNotEquals(
            $this->asserted_string,
            $this->callbackAssertedAsString()
        );

        /**
         * Asserting Equals from overriden
         */
        $this->assertEquals(
            $this->asserted_string_overriden,
            $this->callbackAssertedAsString()
        );

        /**
         * remove the previous callback, so it must back onto standard
         * Because Callback using dynamic object uses Hookable::removeAll()
         * and set the priority check to make sure it was removed
         */
        $this->hook->removeAll(
            $this->asserted_string_callback_name,
            90
        );

        /**
         * Asserting Equals from overriden
         */
        $this->assertNotEquals(
            $this->asserted_string_overriden,
            $this->callbackAssertedAsString()
        );

        /**
         * Asserting Equals from Original
         */
        $this->assertEquals(
            $this->asserted_string,
            $this->callbackAssertedAsString()
        );
    }

    /**
     * @return mixed dynamic result
     */
    public function callbackAssertArray(array $additional_array = array())
    {
        $assert_array_merged = array_merge(
            /**
             * Return values @array
             */
            array(
                'keyname' => true
            ),
            $additional_array
        );

        return $this->hook->apply(
            'array_example_callback', // callback name
            $assert_array_merged
        );
    }

    public function testAssertElse()
    {
        /**
         * Assery array has key
         */
        $this->assertArrayHasKey(
            'keyname',
            $this->callbackAssertArray()
        );

        $c = $this;
        $this->hook->add(
            'array_example_callback', // callback name
            function () use ($c) {
                return $c->callbackAssertArray(array('keyname_2' => true));
            }
        );

        /**
         * assert Check if Hooks exists
         */
        $this->assertTrue(
            $this->hook->has('array_example_callback')
        );

        /**
         * assert Check if Hooks not exists
         */
        $this->assertFalse(
            $this->hook->has('nocallback')
        );
    }
}
