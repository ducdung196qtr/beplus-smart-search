import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import Edit from './edit';
import metadata from './block.json';
import type { BlockAttributes } from './types';

registerBlockType( metadata as unknown as BlockConfiguration< BlockAttributes >, {
	edit: Edit,
	save: () => null,
} );
