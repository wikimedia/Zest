<?php

use Wikimedia\Zest\ZestInst;

class ZestInstTest extends \PHPUnit\Framework\TestCase {
	/**
	 * Just check if the Zest class has no syntax error.
	 *
	 * This is just a simple check to make sure your library has no
	 * syntax error. This helps you troubleshoot any typo before you
	 * even use this library in a real project.
	 *
	 */
	public function testIsThereAnySyntaxError() {
		$var = new ZestInst;
		$this->assertTrue( is_object( $var ) );
	}

	/**
	 * @dataProvider unquoteProvider
	 */
	public function testUnquote( $given, $expected ) {
		$unquote = self::getPrivateMethod( ZestInst::class, 'unquote' );
		$var = $unquote->invoke( null, $given );
		$this->assertSame( $var, $expected );
	}
	public function unquoteProvider() {
		return [
			[ 'foo', 'foo' ],
			[ '"foo"', 'foo' ],
			[ "'foo'", 'foo' ],
			[ "'\x41\x42'", 'AB' ],
		];
	}

	/**
	 * @dataProvider parseNthProvider
	 */
	public function testParseNth( $given, $group, $offset ) {
		$unquote = self::getPrivateMethod( ZestInst::class, 'parseNth' );
		$res = $unquote->invoke( null, $given );
		$this->assertSame( $res->group, $group );
		$this->assertSame( $res->offset, $offset );
	}
	public function parseNthProvider() {
		return [
			[ 'even', 2, 0 ],
			[ 'odd', 2, 1 ],
			[ '+3n+45', 3, 45 ],
			[ '-3n-45', -3, -45 ],
			[ '-2n+1', -2, 1 ],
		];
	}

	public function testCustom() {
		$doc = self::loadHTML( __DIR__ . "/index.html" );
		$thrown = 0;
		$z0 = new ZestInst;
		// Verify that we can create a custom selector
		$z1 = new ZestInst;
		$z1->addSelector0( ':zesttest', function ( DOMNode $el ):bool {
			return strtolower( $el->nodeName ) === 'footer' &&
				strtolower( $el->parentNode->nodeName ) === 'article';
		} );
		$matches = $z1->find( ':zesttest', $doc );
		$this->assertSame( count( $matches ), 1 );
		$this->assertSame( self::toXPath( $matches[0] ), '/html[1]/body[1]/article[1]/footer[1]' );

		// Verify that this new selector doesn't infect previously- or
		// subsequently-created selector engines.
		try {
			$z0->find( ':zesttest', $doc );
		} catch ( \Exception $e ) {
			$thrown++;
		}
		$z2 = new ZestInst;
		try {
			$z2->find( ':zesttest', $doc );
		} catch ( \Exception $e ) {
			$thrown++;
		}
		$this->assertSame( 2, $thrown );
	}

	public static function toXPath( DOMNode $node ) {
		return ZestTest::toXPath( $node );
	}
	public static function loadHtml( string $filename ) : DOMDocument {
		return ZestTest::loadHtml( $filename );
	}

	/**
	 * Get a private or protected method for testing/documentation purposes.
	 * How to use for MyClass->foo():
	 *      $cls = new MyClass();
	 *      $foo = PHPUnitUtil::getPrivateMethod($cls, 'foo');
	 *      $foo->invoke($cls, $...);
	 * @param object $obj The instantiated instance of your class
	 * @param string $name The name of your private/protected method
	 * @return ReflectionMethod The method you asked for
	 */
	public static function getPrivateMethod( $obj, $name ) {
		$class = new ReflectionClass( $obj );
		$method = $class->getMethod( $name );
		$method->setAccessible( true );
		return $method;
	}
}
