/**
 * WordPress dependencies
 */
import { useState, useCallback } from '@wordpress/element';

export const useNavigationTreeNodes = () => {
	const [ nodes, setNodes ] = useState( {} );

	const getNode = ( key ) => nodes[ key ];

	const addNode = useCallback( ( key, value ) => {
		// eslint-disable-next-line no-unused-vars
		const { children, ...newNode } = value;
		return setNodes( ( original ) => ( {
			...original,
			[ key ]: newNode,
		} ) );
	}, [] );

	const removeNode = useCallback( ( key ) => {
		return setNodes( ( original ) => {
			// eslint-disable-next-line no-unused-vars
			const { [ key ]: removedNode, ...remainingNodes } = original;
			return remainingNodes;
		} );
	}, [] );

	return { nodes, getNode, addNode, removeNode };
};
