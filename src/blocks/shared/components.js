/**
 * WordPress dependencies
 */
const {
    useInstanceId
} = wp.compose;

/**
 * Internal dependencies
 */
const {
    BaseControl
} = wp.components;

export function OnBlurTextControl( {
	label,
	hideLabelFromVision,
	value,
	help,
	className,
    onChange,
    onBlur,
	type = 'text',
	...props
} ) {
	const instanceId = useInstanceId( OnBlurTextControl );
	const id = `inspector-text-control-${ instanceId }`;

	return (
		<BaseControl
			label={ label }
			hideLabelFromVision={ hideLabelFromVision }
			id={ id }
			help={ help }
			className={ className }
		>
			<input
				className="components-text-control__input"
				type={ type }
				id={ id }
				value={ value }
                onChange={ (event) => {
                    onChange(event.target.value);
                } }
                onBlur={ (event) => onBlur() }
				aria-describedby={ !! help ? id + '__help' : undefined }
				{ ...props }
			/>
		</BaseControl>
	);
}
