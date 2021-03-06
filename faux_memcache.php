<?php

/**
 * Faux Memcache
 * 
 * A toy implimentation of some of the basic features of memcache
 * 
 * @author Nicholas Sardo
 * @copyright 2014-2018 Nicholas Sardo
 * @license MIT
 * @version 1.0.0
 * Date: 3/19/14
 */

    /** 
     * @global int max size of memory cache
     * */
    define('SIZE', 16000000);

    /** @global mixed the **global** cache */
    $cache  = array();

    /** @global int **global** cache item expiry times */
    $gc     = array( array() );

echo <<<'EOT'
Testing REPL
Commands are: add, get, set, increment, decrement, delete.
Quit with: exit or quit.
EOT;
        
        echo "\n> ";

        while( $c = fread( STDIN, 512 ) ) 
        {

            if ( preg_match( '/exit/i', $c )  || preg_match( '/quit/i', $c ) ) { exit; }
            preg_match( '/(\w+)\s(\w+)?\s?(\w+)?\s?(\w+)?\s?/i', $c, $matches );

            $cmd = $matches[1];
            if ( count( $matches ) <= 2 ) continue;

            $vrb = $matches[2];
            if ( count($matches) == 3 )
            {
                if ( function_exists( $cmd ) ){ $cmd( $vrb ); }
            }

            if ( count( $matches ) == 4 )
            {
                $sbj = $matches[3];
                if ( function_exists( $cmd ) ) { $cmd( $vrb, $sbj ); }
            }

            if ( count( $matches ) == 5 )
            {
                $sbj = $matches[3];
                $exp = $matches[4];
                if ( function_exists( $cmd ) ) { $cmd( $vrb, $sbj, $exp ); }
            }
            echo "> ";
        }


    /**
     * Ensure cache doesn't go over 16M bytes
     * 
     * Test's the array's potential finishing size after adding this value
     * 
     * @param int|string $val value to add to cache
     * @return bool
     * 
     */
    function check_array_size( $val )
    {
        global $cache;

        if ( !is_numeric( $val ) ) $serializedVal = serialize( $cache );

        $serializedCache = serialize( $cache );
        $serializedVal   = serialize( $val );
        
        try {
                if ( function_exists( 'mb_strlen' ) ) 
                {
                    $size = mb_strlen( $serializedCache, '8bit' );
                } 
                else 
                {
                    $size = strlen( $serializedCache );
                }
                return (int)$serializedCache + (int)$serializedVal >= SIZE ? false : true;
        } catch ( Exception $e ) {
                echo 'An exception occurred: ',  $e->getMessage(), "\n";
        }
    }

    /**
     * Cache expiry check
     * 
     * Checks for expiring/expired key and removes it
     * DISPLAY's *'EVICTED EXPIRED KEY'* if key is expired
     * 
     * @param string $k a key
     * @return bool 
     */
    function check_time( $k )
    {
        global $cache, $gc;

        if( ! is_string($k) ) { echo 'not string'; return; }

        if( array_key_exists( $k, $gc ) )
            if( date( "m/d/Y h:i:s a", time() ) > $gc[$k]['expires'] ) 
            {
                try {
                        unset( $cache[$k] );
                        unset( $gc[$k]['expires'] );
                        echo 'EVICTED EXPIRED KEY';
                        return true;
                } catch ( Exception $e ) {
                        echo 'An exception occurred: ',  $e->getMessage(), "\n";
                }
                return false;
            }
    }

    /**
     * Sets the item associated w/ the key to specified value. 
     * Expire is in # seconds.
     * 
     * @param string $k a key
     * @param int|string $v a value
     * @param int $exp **(optional)** expire
     * @return string STORED if successful
     */
    function set( $k, $v, $exp = NULL )
    {
        global $cache, $gc;

        if ( strlen( $k ) > 16  ) { return; }
        if ( strlen( $v ) > 255 ) { return; }
        if ( is_numeric($v) ) $v = (int)$v;
        
        if ( ! is_string($k) ) {
            echo 'KEY MUST BE A STRING';
            return;
        }

        if ( check_array_size( $v ) ) 
        {
            try {
                    $cache[$k] = $v;
                    if( $exp != NULL ) $gc[$k]['expires'] = date( "m/d/Y h:i:s a", time() + $exp );
            
                    echo 'STORED' . "\n";
            } catch ( Exception $e ) {
                echo 'An exception occurred: ',  $e->getMessage(), "\n";
            }
        }
        else
        {
            echo 'ERROR --CACHE FULL';
        }
    }

    /**
     * Retrieves item from cache.
     * 
     * @param string $k a key
     * @return int|string the value contained in the key or **KEY DOES NOT EXIST**
     */
    function get( $k )
    {
        global $cache, $gc;

        if( ! is_string($k) || ! array_key_exists($k, $cache) ) { 
            echo 'KEY DOES NOT EXIST';
            return; 
        }

        if( array_key_exists( $k, $cache ) ) 
        {
            try {
                    if( isset( $gc[$k]['expires'] ) )
                        if( check_time( $k ) ) { return; }
                    echo $cache[$k] . "\n";
            } catch ( Exception $e ) {
                    echo 'An exception occurred: ',  $e->getMessage(), "\n";
            }
        }
    }

    /** 
     * Adds the key and associated value to the cache,
     * if the specified key does not already exist.
     * 
     * @param string $k a key
     * @param int|string $v a value
     * @param int $exp OPTIONAL expire
     * @return string STORED on success, **ignore's** existing key
     */
    function add( $k, $v, $exp = null )
    {
        global $cache;

        if( ! is_string( $k ) )  { 'not string -add'; return; }
        if( strlen( $k ) > 16  ) { return; }
        if( strlen( $v ) > 255 ) { return; }
        if( is_numeric($v) ) $v = (int)$v;

        if( ! is_string($k) ) {
            echo 'KEY MUST BE A STRING';
            return;
        }

        if( check_array_size( $v ) )
        {
            if ( !array_key_exists( $k, $this->cache) ) 
            {
                try {
                        $this->cache[$k] = $v;
                        if( $exp != NULL ) $this->gc[$k]['expires'] = date( "m/d/Y h:i:s a", time() + $exp );
                        echo 'STORED' . "\n";
                } catch ( Exception $e ) {
                        echo 'An exception occurred: ',  $e->getMessage(), "\n";
                }
            } 
        } 
        else 
        {
            echo 'ERROR --CACHE FULL' . "\n";
        }
    }

    /**
     * Increment the item associated w/ the key by one, or by the optional amount.
     * Returns current count on success, *'KEY DOES NOT EXIST'* on non-existent key
     * *Ignore's* action on Strings
     * 
     * @param string $k a key
     * @param int $a **optional** increment amount, **DEFAULT** is 1
     * @return int the newly incremented value
     */
    function increment( $k, $a = 1 )
    {
        global $cache;
        
        if ( ! is_string($k) ) { return; }
        if ( is_string( $cache[$k] ) ) { return; }

        if ( ! array_key_exists( $k, $cache ) ) {
            echo 'KEY DOES NOT EXIST';
            return;
        }

        $t = $cache[$k] + $a;
        if ( check_array_size( $t ) ) 
        {
            if ( is_numeric( $cache[$k] ) )
            {
                try {
                        echo ( $cache[$k] += $a ) . "\n";
                } catch ( Exception $e ) {
                    echo 'An exception occurred: ',  $e->getMessage(), "\n";
                }
            }
        }
        else
        {
            echo 'ERROR --CACHE IS FULL' . "\n";
        }
    }

    /**
     * Decrement the item associated w/ the key by one, or by the optional amount.
     * Returns current count on success, *'KEY DOES NOT EXIST'* on non-existent key,
     * *Ignore's* action on Strings
     * 
     * @param string $k a key
     * @param int|string $a **optional** decrement amount, **DEFAULT** is 1
     * @return int the newly decremented value
     */
    function decrement( $k, $a = 1 )
    {
        global $cache;

        if( ! is_string($k) ) { return; }
        if ( is_string( $cache[$k] ) ) { return; }

        if ( ! array_key_exists( $k, $cache ) ) {
            echo "KEY DOES NOT EXIST";
            return;
        }

        if ( is_numeric( $cache[$k] ) )
        {
            try {
                echo ( $cache[$k] -= $a ) . "\n";
            } catch ( Exception $e ) {
                echo 'An exception occurred: ',  $e->getMessage(), "\n";
            }
        }
    }

    /**
     * Delete the key and associated data from the cache.
     * Returns *'DELETED'* on success, *'KEY DOES NOT EXIST'* on non-existent key
     * 
     * @param string $k a key
     * @return string "DELETED"
     */
    function delete( $k )
    {
        global $cache;

        if( ! is_string($k) ) { return; }

        if ( ! array_key_exists( $k, $cache ) ) {
            echo "KEY DOES NOT EXIST";
            return;
        } 

        try {
                unset( $cache[$k] );
                if ( isset( $gc[$k]['expired'] ) ) unset( $gc[$k]['expired'] );
                echo 'DELETED' . "\n";
        } catch ( Exception $e ) {
            echo 'An exception occurred: ',  $e->getMessage(), "\n";
        }
    }
