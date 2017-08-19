<?php


namespace Rocket\Footer\JS\Lazyload;


class Videos extends LazyloadAbstract {
	/**
	 * @param string  $content
	 *
	 * @param  string $src
	 *
	 * @return void
	 */
	protected function do_lazyload( $content, $src ) {

	}

	protected function after_do_lazyload() {
		$oembed = _wp_oembed_get_object();
		foreach ( $this->get_tag_collection( 'iframe' ) as $tag ) {
			if ( $this->is_no_lazyload( $tag ) ) {
				continue;
			}
			$src = $tag->getAttribute( 'data-src' );
			if ( empty( $src ) ) {
				$src = $tag->getAttribute( 'src' );
			}
			$src  = $this->maybe_translate_url( $src );
			$info = $oembed->get_data( $src );
			if ( ! empty( $info ) && 'video' === $info->type ) {
				$img = $this->create_tag( 'img' );
				$img->setAttribute( 'data-src', $this->download_image( $info->thumbnail_url ) );
				$img->setAttribute( 'width', $info->thumbnail_width );
				$img->setAttribute( 'style', 'max-width:100%;height:auto;cursor:pointer;' );
				$img->setAttribute( 'data-lazy-video-embed', "lazyload-video-{$this->instance}" );
				$tag->parentNode->insertBefore( $img, $tag );
				$this->lazyload_script( $this->get_tag_content( $tag ), "lazyload-video-{$this->instance}", $tag );
				$this->instance ++;
			}
		}
	}

	/**
	 * @param string $url
	 */
	private function maybe_translate_url( $url ) {
		$url = parse_url( $url );
		if ( 'youtube.com' === $url['host'] || 'www.youtube.com' === $url['host'] ) {
			if ( false !== strpos( $url['path'], 'embed' ) ) {
				$video_id     = pathinfo( $url['path'], PATHINFO_FILENAME );
				$url['path']  = '/watch';
				$url['query'] = http_build_query( [ 'v' => $video_id ] );
			}
		}
		$url = http_build_url( $url );

		return $url;
	}

	private function download_image( $url ) {
		$data = $this->plugin->remote_fetch( $url );
		if ( ! empty( $data ) ) {
			$url_parts = parse_url( $url );
			$info      = pathinfo( $url_parts['path'] );
			if ( empty( $url_parts['port'] ) ) {
				$url_parts['port'] = '';
			}
			$hash      = md5( $url_parts['scheme'] . '://' . $info['dirname'] . ( ! empty( $url_parts['port'] ) ? ":{$url_parts['port']}" : '' ) . '/' . $info['filename'] );
			$filename  = $this->plugin->get_cache_path() . $hash . '.' . $info['extension'];
			$final_url = get_rocket_cdn_url( set_url_scheme( str_replace( WP_CONTENT_DIR, WP_CONTENT_URL, $filename ) ) );
			if ( ! $this->plugin->get_wp_filesystem()->is_file( $filename ) ) {
				$this->plugin->put_content( $filename, $data );
			}
			$url = $final_url;
		}

		return $url;
	}

	protected function is_match( $content, $src ) {
		return false;
	}
}