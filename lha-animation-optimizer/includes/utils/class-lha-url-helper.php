<?php
/**
 * URL Helper utilities for LHA Animation Optimizer.
 *
 * @package    LHA_Animation_Optimizer
 * @subpackage LHA_Animation_Optimizer/includes/utils
 * @author     LHA Plugin Author <author@example.com>
 * @since      2.0.0
 */

namespace LHA\Animation_Optimizer\Utils;

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Url_Helper {

	/**
	 * Converts a potentially relative URL to an absolute URL based on a base URL.
	 *
	 * @since 2.0.0
	 * @param string $url       The URL to make absolute. Can be relative or absolute.
	 * @param string $base_url  The base URL to resolve relative URLs against.
	 * @return string|false     The absolute URL, or false on failure to parse.
	 */
	public static function make_absolute( $url, $base_url ) {
		if ( ! $url || ! $base_url ) {
			return $url; // Return original if essential parts are missing
		}

		// If URL is already absolute, return it.
		if ( parse_url( $url, PHP_URL_SCHEME ) !== null ) {
			return $url;
		}

		$base_parts = parse_url( $base_url );
		if ( ! $base_parts ) {
			return $url; // Could not parse base_url
		}
		
		$scheme = isset( $base_parts['scheme'] ) ? $base_parts['scheme'] : 'http';
		$host = isset( $base_parts['host'] ) ? $base_parts['host'] : '';
		$port = isset( $base_parts['port'] ) ? ':' . $base_parts['port'] : '';
		$path = isset( $base_parts['path'] ) ? $base_parts['path'] : '/';

		if ( empty( $host ) ) {
			return $url; // Base URL must have a host
		}

		// If URL starts with '//', it's protocol-relative, use base scheme
		if ( substr( $url, 0, 2 ) === '//' ) {
			return $scheme . ':' . $url;
		}

		// If URL starts with '/', it's an absolute path
		if ( substr( $url, 0, 1 ) === '/' ) {
			return $scheme . '://' . $host . $port . $url;
		}

		// It's a relative path, resolve it against the base URL's path
		// Remove filename from base path if present
		$base_path_dir = dirname( $path );
		if ( $base_path_dir === '.' || $base_path_dir === '/' ) {
			$base_path_dir = '';
		}
		
		// Canonicalize path (remove . and .. segments)
		$absolute_path = $base_path_dir . '/' . $url;
		$absolute_path_parts = array();
		$path_segments = explode('/', $absolute_path);

		foreach ($path_segments as $segment) {
			if ($segment == '.') {
				continue;
			}
			if ($segment == '..') {
				array_pop($absolute_path_parts);
			} else {
				$absolute_path_parts[] = $segment;
			}
		}
		$resolved_path = implode('/', $absolute_path_parts);
		// Ensure leading slash if not empty
		if (strlen($resolved_path) > 0 && $resolved_path[0] !== '/') {
			$resolved_path = '/' . $resolved_path;
		}


		return $scheme . '://' . $host . $port . $resolved_path;
	}
}
?>
