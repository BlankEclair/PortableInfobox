<?php

use Wikia\PortableInfobox\Helpers\PortableInfoboxRenderServiceHelper;

class MobileInfoboxRenderService extends WikiaService {
	const MEDIA_CONTEXT_INFOBOX_HERO_IMAGE = 'infobox-hero-image';
	const MEDIA_CONTEXT_INFOBOX = 'infobox';

	private static $templates = [
		'wrapper' => 'PortableInfoboxWrapper.mustache',
		'title' => 'PortableInfoboxItemTitle.mustache',
		'header' => 'PortableInfoboxItemHeader.mustache',
		'image' => 'PortableInfoboxItemImage.mustache',
		'image-mobile' => 'PortableInfoboxItemImageMobile.mustache',
		'image-mobile-wikiamobile' => 'PortableInfoboxItemImageMobileWikiaMobile.mustache',
		'data' => 'PortableInfoboxItemData.mustache',
		'group' => 'PortableInfoboxItemGroup.mustache',
		'horizontal-group-content' => 'PortableInfoboxHorizontalGroupContent.mustache',
		'navigation' => 'PortableInfoboxItemNavigation.mustache',
		'hero-mobile' => 'PortableInfoboxItemHeroMobile.mustache',
		'hero-mobile-wikiamobile' => 'PortableInfoboxItemHeroMobileWikiaMobile.mustache',
		'image-collection' => 'PortableInfoboxItemImageCollection.mustache',
		'image-collection-mobile' => 'PortableInfoboxItemImageCollectionMobile.mustache',
		'image-collection-mobile-wikiamobile' => 'PortableInfoboxItemImageCollectionMobileWikiaMobile.mustache'
	];
	private $templateEngine;
	private $imagesWidth;

	function __construct() {
		parent::__construct();
		$this->templateEngine = ( new Wikia\Template\MustacheEngine )
			->setPrefix( self::getTemplatesDir() );
		$this->imagesWidth = PortableInfoboxRenderServiceHelper::MOBILE_THUMBNAIL_WIDTH;
	}

	public static function getTemplatesDir() {
		return dirname( __FILE__ ) . '/../templates';
	}

	public static function getTemplates() {
		return self::$templates;
	}

	/**
	 * renders infobox
	 *
	 * @param array $infoboxdata
	 *
	 * @param $theme
	 * @param $layout
	 * @return string - infobox HTML
	 */
	public function renderInfobox( array $infoboxdata, $theme, $layout ) {
		wfProfileIn( __METHOD__ );

		$helper = new PortableInfoboxRenderServiceHelper();
		$infoboxHtmlContent = '';
		$heroData = [ ];

		foreach ( $infoboxdata as $item ) {
			$data = $item[ 'data' ];
			$type = $item[ 'type' ];

			if ( $helper->isValidHeroDataItem( $item, $heroData ) ) {
				$heroData[ $type ] = $data;
				continue;
			} elseif ( $helper->isTypeSupportedInTemplates( $type, self::getTemplates() ) ) {
				$infoboxHtmlContent .= $this->renderItem( $type, $data );
			}
		}

		if ( !empty( $heroData ) ) {
			$infoboxHtmlContent = $this->renderInfoboxHero( $heroData ) . $infoboxHtmlContent;
		}

		if ( !empty( $infoboxHtmlContent ) ) {
			$output = $this->renderItem( 'wrapper', [ 'content' => $infoboxHtmlContent ] );
		} else {
			$output = '';
		}

		\Wikia\PortableInfobox\Helpers\PortableInfoboxDataBag::getInstance()->setFirstInfoboxAlredyRendered( true );

		wfProfileOut( __METHOD__ );

		return $output;
	}

	/**
	 * Produces HTML output for item type and data
	 *
	 * @param $type
	 * @param $template
	 * @param array $data
	 * @return string
	 */
	private function render( $type, $template, array $data ) {
		$data = SanitizerBuilder::createFromType( $type )->sanitize( $data );

		return $this->templateEngine->clearData()
			->setData( $data )
			->render( self::getTemplates()[ $template ] );
	}

	/**
	 * renders part of infobox
	 *
	 * @param string $type
	 * @param array $data
	 *
	 * @return bool|string - HTML
	 */
	private function renderItem( $type, array $data ) {
		if ( $type === 'group' ) {
			return $this->renderGroup( $data );
		}

		if ( $type === 'image' ) {
			return $this->renderImage( $data );
		}

		return $this->render( $type, $type, $data );
	}

	/**
	 * renders group infobox component
	 *
	 * @param array $groupData
	 *
	 * @return string - group HTML markup
	 */
	private function renderGroup( $groupData ) {
		$cssClasses = [ ];
		$helper = new PortableInfoboxRenderServiceHelper();
		$groupHTMLContent = '';
		$dataItems = $groupData[ 'value' ];
		$layout = $groupData[ 'layout' ];
		$collapse = $groupData[ 'collapse' ];

		if ( $layout === 'horizontal' ) {
			$groupHTMLContent .= $this->renderItem(
				'horizontal-group-content',
				$helper->createHorizontalGroupData( $dataItems )
			);
		} else {
			foreach ( $dataItems as $item ) {
				$type = $item[ 'type' ];

				if ( $helper->isTypeSupportedInTemplates( $type, self::getTemplates() ) ) {
					$groupHTMLContent .= $this->renderItem( $type, $item[ 'data' ] );
				}
			}
		}

		if ( $collapse !== null && count( $dataItems ) > 0 && $dataItems[ 0 ][ 'type' ] === 'header' ) {
			$cssClasses[] = 'pi-collapse';
			$cssClasses[] = 'pi-collapse-' . $collapse;
		}

		return $this->render( 'group', 'group', [
			'content' => $groupHTMLContent,
			'cssClasses' => implode( ' ', $cssClasses )
		] );
	}

	/**
	 * renders infobox hero component
	 *
	 * @param array $data - infobox hero component data
	 *
	 * @return string
	 */
	private function renderInfoboxHero( $data ) {
		$helper = new PortableInfoboxRenderServiceHelper();

		// In Mercury SPA content of the first infobox's hero module has been moved to the article header.
		$firstInfoboxAlredyRendered = \Wikia\PortableInfobox\Helpers\PortableInfoboxDataBag::getInstance()
			->isFirstInfoboxAlredyRendered();

		if ( array_key_exists( 'image', $data ) ) {
			$image = $data[ 'image' ][ 0 ];
			$image[ 'context' ] = self::MEDIA_CONTEXT_INFOBOX_HERO_IMAGE;
			$image = $helper->extendImageData( $image, PortableInfoboxRenderServiceHelper::MOBILE_THUMBNAIL_WIDTH );
			$data[ 'image' ] = $image;

			if ( !$helper->isMercury() ) {
				return $this->renderItem( 'hero-mobile-wikiamobile', $data );
			} elseif ( $firstInfoboxAlredyRendered ) {
				return $this->renderItem( 'hero-mobile', $data );
			}
		} elseif ( !$helper->isMercury() || $firstInfoboxAlredyRendered ) {
			return $this->renderItem( 'title', $data[ 'title' ] );
		}

		return '';
	}

	private function renderImage( $data ) {
		$images = [ ];
		$helper = new PortableInfoboxRenderServiceHelper();

		for ( $i = 0; $i < count( $data ); $i++ ) {
			$data[ $i ][ 'context' ] = self::MEDIA_CONTEXT_INFOBOX;
			$data[ $i ] = $helper->extendImageData( $data[ $i ], $this->imagesWidth );

			if ( !!$data[ $i ] ) {
				$images[] = $data[ $i ];
			}
		}

		if ( count( $images ) === 0 ) {
			return '';
		}

		// use different template for wikiamobile
		if ( !$helper->isMercury() ) {
			// always display only the first image on WikiaMobile
			$data = $images[ 0 ];
			$templateName = 'image-mobile-wikiamobile';
		} else {
			if ( count( $images ) === 1 ) {
				$data = $images[ 0 ];
				$templateName = 'image-mobile';
			} else {
				// more than one image means image collection
				$data = $helper->extendImageCollectionData( $images );
				$templateName = 'image-collection-mobile';
			}
		}

		return $this->render( 'image', $templateName, $data );
	}
}
