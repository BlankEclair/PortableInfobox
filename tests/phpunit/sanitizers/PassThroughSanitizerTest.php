<?php

use PortableInfobox\Sanitizers\SanitizerBuilder;
use PortableInfobox\Sanitizers\PassThroughSanitizer;

/**
 * @group PortableInfobox
 * @covers PortableInfobox\Sanitizers\NodeSanitizer
 * @covers PortableInfobox\Sanitizers\PassThroughSanitizer
 */
class PassThroughSanitizerTest extends MediaWikiTestCase {
	/** @var PortableInfobox\Sanitizers\PassThroughSanitizer $sanitizer */
	private $sanitizer;

	protected function setUp() {
		$this->sanitizer = SanitizerBuilder::createFromType('invalid-type');
		parent::setUp();
	}

	protected function tearDown() {
		unset( $sanitizer );
		parent::tearDown();
	}

	/**
	 * @param $data
	 * @param $expected
	 * @dataProvider sanitizeDataProvider
	 */
	function testSanitize( $data, $expected ) {
		$this->assertEquals(
			$expected,
			$this->sanitizer->sanitize( $data )
		);
	}

	function sanitizeDataProvider() {
		return [
			[
				['value' => 'Test Title' ],
				[ 'value' => 'Test Title' ]
			],
			[
				['value' => '  Test Title    '],
				['value' => '  Test Title    '],
			],
			[
				['value' => 'Test Title <img src=\'data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw%3D%3D\' class=\'article-media\' data-ref=\'1\' width=\'400\' height=\'100\' /> ' ],
				['value' => 'Test Title <img src=\'data:image/gif;base64,R0lGODlhAQABAIABAAAAAP///yH5BAEAAAEALAAAAAABAAEAQAICTAEAOw%3D%3D\' class=\'article-media\' data-ref=\'1\' width=\'400\' height=\'100\' /> ' ],
			],
			[
				['value' => 'Test Title <a href="example.com">with link</a>'],
				['value' => 'Test Title <a href="example.com">with link</a>'],
			],
			[
				['value' => 'Real world <a href="http://vignette-poz.wikia-dev.com/mediawiki116/images/b/b6/DBGT_Logo.svg/revision/latest?cb=20150601155347" 	class="image image-thumbnail" 	 	 	><img src="http://vignette-poz.wikia-dev.com/mediawiki116/images/b/b6/DBGT_Logo.svg/revision/latest/scale-to-width-down/30?cb=20150601155347" 	 alt="DBGT Logo"  	class="" 	 	data-image-key="DBGT_Logo.svg" 	data-image-name="DBGT Logo.svg" 	 	 width="30"  	 height="18"  	 	 	 	></a>title example'] ,
				['value' => 'Real world <a href="http://vignette-poz.wikia-dev.com/mediawiki116/images/b/b6/DBGT_Logo.svg/revision/latest?cb=20150601155347" 	class="image image-thumbnail" 	 	 	><img src="http://vignette-poz.wikia-dev.com/mediawiki116/images/b/b6/DBGT_Logo.svg/revision/latest/scale-to-width-down/30?cb=20150601155347" 	 alt="DBGT Logo"  	class="" 	 	data-image-key="DBGT_Logo.svg" 	data-image-name="DBGT Logo.svg" 	 	 width="30"  	 height="18"  	 	 	 	></a>title example'] ,
			],
		];
	}
}
