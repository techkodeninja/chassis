<?php
/**
 * Badge template tag, for Novaris views.
 *
 *     <?= $engine->badge( 'Featured' ) ?>
 *
 * @package Techkodeninja\Chassis
 */

namespace Techkodeninja\Chassis\Tags;

use Novaris\Template\Tag\Tag;

class Badge extends Tag
{
	/**
	 * @var string
	 */
	protected $label;

	/**
	 * @var string[]
	 */
	protected $modifiers;

	public function __construct( string $label, array $modifiers = [] )
	{
		$this->label     = $label;
		$this->modifiers = $modifiers;
	}

	public function toHtml(): string
	{
		return app( 'chassis' )->badge( $this->label, $this->modifiers );
	}

	public function toText(): string
	{
		return $this->label;
	}
}
