<?php
/**
 * Wiki Category Tag Cloud
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen http://daniel.friesen.name
 *
 * Derived from: YetAnotherTagCloud http://orangedino.com
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class WikiCategoryTagCloud {
	/**
	 * Register the new <tagcloud> tag with the Parser.
	 */
	public static function register( &$parser ) {
		$parser->setHook( 'tagcloud', [ __CLASS__, 'renderTagCloud' ] );
		return true;
	}

	/**
	 * When an admin edits MediaWiki:Tagcloudpages, purge the cache for each page
	 * listed on that message.
	 */
	public static function invalidateCache( WikiPage &$wikiPage, &$user, &$content, &$summary, $isMinor, $isWatch, $section, &$flags, &$status ) {
		$at = $wikiPage->getTitle();

		if ( $at->getText() == 'Tagcloudpages' && $at->getNamespace() == NS_MEDIAWIKI ) {
			$message = wfMessage( 'tagcloudpages' )->inContentLanguage();

			// If it's empty, do nothing.
			if ( $message->isDisabled() ) {
				return true;
			}

			$titles = explode( "\n", $message->plain() );

			for ( $i = 0; $i < count( $titles ); $i++ ) {
				$t = Title::newFromText( $titles[$i] );
				if ( $t ) {
					$t->invalidateCache();
				}
			}
		}

		return true;
	}

	/**
	 * Callback function for register() which renders the HTML that this tag
	 * extension outputs.
	 */
	public static function renderTagCloud( $input, $params, $parser ) {
		$MIN_SIZE = 77;
		$INCREASE_FACTOR = 100;

		// Add CSS into the output via ResourceLoader
		$parser->getOutput()->addModuleStyles( 'ext.wikicategorytagcloud' );

		$dbr = wfGetDB( DB_REPLICA );

		$cloudStyle = ( isset( $params['style'] ) ? Sanitizer::checkCss( $params['style'] ) : '' );
		$cloudClasses = preg_split( '/\s+/', ( isset( $params['class'] ) ? htmlspecialchars( $params['class'], ENT_QUOTES ) : '' ) );
		array_unshift( $cloudClasses, 'tagcloud' );
		$linkStyle = ( isset( $params['linkstyle'] ) ? Sanitizer::checkCss( $params['linkstyle'] ) : '' );
		$linkClasses = preg_split( '/\s+/', ( isset( $params['linkclass'] ) ? htmlspecialchars( $params['linkclass'], ENT_QUOTES ) : '' ) );
		$minCountInput = self::getBoxExtensionOption( $input, 'min_count', true );
		$minSizeInput = self::getBoxExtensionOption( $input, 'min_size', true );
		$increaseFactorInput = self::getBoxExtensionOption( $input, 'increase_factor', true );
		if ( $minSizeInput != null ) {
			$MIN_SIZE = $minSizeInput;
		}
		if ( $increaseFactorInput != null ) {
			$INCREASE_FACTOR = $increaseFactorInput;
		}
		if ( $minCountInput == null ) {
			$minCountInput = 0;
		}

		$excludedInput = self::getBoxExtensionOption( $input, 'exclude' );

		$excludeCondition = [];
		// If there are categories to be excluded, explode the "exclude=" line
		// along commas and create an appropriate NOT IN condition for the SQL
		// query below, escaping everything properly.
		if ( strlen( $excludedInput ) > 0 ) {
			$excludedCategories = explode( ',', $excludedInput );
			if ( count( $excludedCategories ) > 0 ) {
				$excludeCondition2 = 'cl_to NOT IN (';
				$excludeCondition2 = $excludeCondition2 . $dbr->makeList( $excludedCategories );
				$excludeCondition = [ $excludeCondition2 . ')' ];
			}
		}

		$res = $dbr->select(
			'categorylinks',
			[ 'cl_to AS title', 'COUNT(*) AS count' ],
			$excludeCondition,
			__METHOD__,
			[
				'GROUP BY' => 'cl_to',
				'HAVING' => 'COUNT(*) >= ' . (int)$minCountInput,
				'ORDER BY' => 'cl_to ASC'
			]
		);
		$count = $dbr->numRows( $res );

		$htmlOut = '';
		$htmlOut = $htmlOut . Html::openElement( 'div', [
			'class' => implode( ' ', $cloudClasses ),
			'style' => $cloudStyle ] );

		$min = 1000000;
		$max = -1;

		for ( $i = 0; $i < $count; $i++ ) {
			$obj = $dbr->fetchObject( $res );
			$tags[$i][0] = $obj->title;
			$tags[$i][1] = $obj->count;
			if ( $obj->count < $min ) {
				$min = $obj->count;
			}
			if ( $obj->count > $max ) {
				$max = $obj->count;
			}
		}

		for ( $i = 0; $i < $count; $i++ ) {
			$textSize = $MIN_SIZE + ( $INCREASE_FACTOR * ( $tags[$i][1] ) ) / ( $max );
			$title = Title::makeTitle( NS_CATEGORY, $tags[$i][0] );
			$style = $linkStyle;
			if ( $style != '' && substr( $style, -1 ) != ';' ) {
				$style .= ';';
			}
			$style .= "font-size: {$textSize}%;";
			$currentRow = '<a class="' . implode( ' ', $linkClasses ) .
				"\" style=\"{$style}\" href=\"" . $title->getLocalURL() . '">' .
				$title->getText() . '</a>&#160; ';
			$htmlOut = $htmlOut . $currentRow;
		}
		$htmlOut = $htmlOut . '</div>';
		return $htmlOut;
	}

	public static function getBoxExtensionOption( $input, $name, $isNumber = false ) {
		if ( preg_match( "/^\s*$name\s*=\s*(.*)/mi", $input, $matches ) ) {
			if ( $isNumber ) {
				return intval( $matches[1] );
			} else {
				return htmlspecialchars( $matches[1], ENT_QUOTES );
			}
		}
	}

}
