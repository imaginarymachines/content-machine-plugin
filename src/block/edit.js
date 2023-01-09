import { __ } from '@wordpress/i18n';

import { useBlockProps } from '@wordpress/block-editor';

import './editor.scss';
import { fetchPrompt, usePostData } from '../usePromptRequest';
import useLoadingStatus from '../useLoadingStatus';

import RunOnce from '../components/RunOnce';
import { LoadingSpinner } from '../components/icons';
export default function Edit( props ) {
	const content = props.attributes.content || '';
	const hasRan = props.attributes.hasRan;
	const setAttributes = props.setAttributes;
	const { getData } = usePostData();

	const insertHanlder = () => {
		setLoading( true );
		const data = getData();
		fetchPrompt( data ).then( ( r ) => {
			if ( r && r.texts ) {
				setAttributes( { content: r.texts[ 0 ], hasRan: true } );
			}
			setLoading( false );
		} );
	};

	const { loading, setLoading } = useLoadingStatus();

	return (
		<p { ...useBlockProps() }>
			{ loading ? <LoadingSpinner /> : null }
			{ ! hasRan ? <RunOnce fn={ insertHanlder } /> : null }
			{ content }
		</p>
	);
}