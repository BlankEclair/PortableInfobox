<?php
namespace Wikia\PortableInfobox\Parser\Nodes;

use Wikia\PortableInfobox\Parser\ExternalParser;

class Node {

	const DATA_SRC_ATTR_NAME = 'source';
	const DEFAULT_TAG_NAME = 'default';
	const VALUE_TAG_NAME = 'value';
	const LABEL_TAG_NAME = 'label';

	protected $xmlNode;

	/* @var $externalParser ExternalParser */
	protected $externalParser;

	public function __construct( \SimpleXMLElement $xmlNode, $infoboxData ) {
		$this->xmlNode = $xmlNode;
		$this->infoboxData = $infoboxData;
	}

	/**
	 * @param mixed $externalParser
	 */
	public function setExternalParser( ExternalParser $externalParser ) {
		$this->externalParser = $externalParser;
	}

	public function getType() {
		return $this->xmlNode->getName();
	}

	public function getData() {
		return [ 'value' => (string) $this->xmlNode ];
	}

	public function isEmpty( $data ) {
		return !( isset( $data[ 'value' ] ) ) || empty( $data[ 'value' ] );
	}

	protected function getValueWithDefault( \SimpleXMLElement $xmlNode ) {
		$source = $this->getXmlAttribute( $xmlNode, self::DATA_SRC_ATTR_NAME );
		$value = null;
		if ( !empty( $source ) ) {
			$value = $this->getInfoboxData( $source );
		}
		if ( !$value ) {
			if ( $xmlNode->{self::DEFAULT_TAG_NAME} ) {
				$value = (string) $xmlNode->{self::DEFAULT_TAG_NAME};
				$value = $this->parseWithExternalParser( $value, true );
			}
		}
		return $value;
	}

	protected function getXmlAttribute( \SimpleXMLElement $xmlNode, $attribute )	{
		if( isset( $xmlNode[ $attribute ] ) )
			return (string) $xmlNode[ $attribute ];
		return null;
	}

	/**
	 * @FIXME: regardless of what is the final approach, this code needs to be explained
	 * WHY it does the things it does. Here. In docblock. Or by phrasing it explicitly with
	 * class and method names.
	 */
	protected function parseWithExternalParser( $data, $recursive = true ) {
		if ( !empty( $data ) && !empty( $this->externalParser ) ) {
			if ( $recursive ) {
				return $this->externalParser->parseRecursive( $data );
			}
			return $this->externalParser->parse( $data );
		}
		return $data;
	}

	protected function getInfoboxData( $key ) {
		$data = isset( $this->infoboxData[ $key ] ) ? $this->infoboxData[ $key ] : null;
		return $this->parseWithExternalParser( $data );
	}
}
