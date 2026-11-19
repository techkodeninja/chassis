<?php
/**
 * Button template tag, for Novaris views.
 *
 *     <?= $engine->button( 'Read more', $url, [ 'primary' ] ) ?>
 *
 * @package Techkodeninja\Chassis
 */

namespace Techkodeninja\Chassis\Tags;

use Novaris\Template\Tag\Tag;

class Button extends Tag
{
	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string
	 */
	protected $url;

	/**
	 * @var string[]
	 */
	protected $modifiers;

	public function __construct( string $label, string $url, array $modifiers = [] )
	{
		$this->label     = $label;
		$this->url       = $url;
		$this->modifiers = $modifiers;
	}

	public function toHtml(): string
	{
		return app( 'chassis' )->button( $this->label, $this->url, $this->modifiers );
	}

	public function toText(): string
	{
		return $this->label;
	}
}
