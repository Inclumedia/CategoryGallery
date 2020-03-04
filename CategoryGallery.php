<?php
/**
 * CategoryGallery MediaWiki extension.
 *
 * This extension implements a <categorygallery> tag creating a gallery of all images in
 * a category.
 *
 * Written by Leucosticte
 * https://www.mediawiki.org/wiki/User:Leucosticte
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Extensions
 */

if( !defined( 'MEDIAWIKI' ) ) {
        echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
        die( 1 );
}

$wgExtensionCredits['parserhook'][] = array(
        'path' => __FILE__,
        'name' => 'CategoryGallery',
        'author' => 'Nathan Larson',
        'url' => 'https://mediawiki.org/wiki/Extension:CategoryGallery',
        'description' => 'Adds <nowiki><categorygallery></nowiki> tag',
        'version' => '1.0.1'
);

$wgExtensionFunctions[] = "CategoryGallery::categoryGallerySetHook";

class CategoryGallery {
        public static function categoryGallerySetHook() {
                global $wgParser;
                $wgParser->setHook( "categorygallery",
                        "CategoryGallery::renderCategoryGallery" );
                $wgParser->setHook( "catgallery",
                        "CategoryGallery::renderCategoryGallery" );
        }

        public static function renderCategoryGallery( $input, $params, $parser ) {
                global $wgBedellPenDragonResident;
                $parser->disableCache();
                if ( !isset( $params['cat'] ) ) { // No category selected
                        return '';
                }
                // Capitalize the first letter in the category argument, convert spaces to _
                $params['cat'] = str_replace ( ' ', '_', ucfirst( $params['cat'] ) );
                // Retrieve category members from database
                $dbr = wfGetDB( DB_REPLICA );
                $res = $dbr->select( 'categorylinks', 'cl_from',
                        array (
                               'cl_to' => $params['cat'],
                               'cl_type' => 'file'
                        )
                );
                $ids = array();
                foreach ( $res as $row ) {
                        $ids[] = $row->cl_from;
                }
                // Create the gallery
                $titles = Title::newFromIDs ( $ids );
                $text = '';
                foreach ( $titles as $title ) {
                        $titlePrefixedDBKey = $title->getPrefixedDBKey();
                        $text .= $titlePrefixedDBKey;
                        if ( isset ( $params['bpdcaption'] ) && $wgBedellPenDragonResident ) {
                                $caption = BedellPenDragon::renderGetBpdProp( $parser,
                                        $titlePrefixedDBKey, $params['bpdcaption'], true, true );
                                if ( $caption !== BPD_NOPROPSET ) {
                                        $text .= "|" .  $caption;
                                }
                        }
                        $text .= "\n";
                }
                $output = $parser->renderImageGallery( $text, $params );
                return $output;
        }
}
