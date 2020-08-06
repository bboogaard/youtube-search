/**
 * WordPress dependencies
 */
const {
    __
} = wp.i18n;

const {
    useInstanceId
} = wp.compose;

import {
    pick,
    unescape as unescapeString
} from 'lodash';

/**
 * Internal dependencies
 */
const {
    BaseControl,
    CheckboxControl,
    FormTokenField
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

export function AutoCompleteField( {
    label,
    value,
    className,
    onChange,
    suggestions,
    help=__('Begin met typen', 'youtube-search'),
    multiple=false
} ) {

    let valuesByName = {};
    let values = [];

    let savedValue = value !== undefined && value !== null ? (multiple ? value : (value.label !== undefined ? [value] : [])) : [];
    savedValue = savedValue.map( (val) => {
        return val.label;
    });

    suggestions.forEach( (suggestion) => {
        valuesByName[suggestion.label] = suggestion;
        values.push(suggestion.label);
    });

    const getValue = (value) => {
        let result = [];
        value.forEach( (val) => {
            if (valuesByName[val] !== undefined) {
                result.push(pick(valuesByName[val], ['label', 'value']));
            }
        });
        return multiple ? result : (result.length ? result.pop() : {});
    }

    return (
        <BaseControl
            className={ (className ? className + ' ' : '') + 'autocomplete' }
            label={ label }
        >
            <FormTokenField
                value={ savedValue }
                suggestions={ values }
                onChange={ (value) => {
                    onChange(getValue(value));
                } }
            />
            <p><em>{ help }</em></p>
        </BaseControl>
    );

}

export function CategoriesField( {
    label,
    value,
    className,
    onChange,
    categories
} ) {

    value = value !== undefined && value !== null ? value : [];

    const buildTree = (elements) => {

        let mappedElements = {};
        elements.forEach( (element) => {
            if (mappedElements[element.parent] === undefined) {
                mappedElements[element.parent] = [];
            }
            mappedElements[element.parent].push(element);
        })

        const makeTree = (parent) => {

            let result = [];
            let children = mappedElements[parent] !== undefined ? mappedElements[parent] : [];
            children.forEach( (child) => {
                result.push([child, makeTree(child.term_id)]);
            });
            return result;

        }

        return makeTree(0);

    }

    const mapObjects = (objects) => {

        if (objects === undefined) {
            return {};
        }

        let result = {};
        objects.forEach( (object) => {
            result[object.term_id] = object;
        });
        return result;

    }

    const unMapObjects = (mappedObjects) => {

        let result = [];
        for (var key in mappedObjects) {
            if (mappedObjects[key]) {
                result.push(mappedObjects[key]);
            }
        }
        return result;

    }

    let categoriesById = mapObjects(categories);
    let valueById = mapObjects(value);

    const updateCategories = (categoryId, isSelected) => {

        if (isSelected) {
            if (categoriesById[categoryId] !== undefined) {
                valueById[categoryId] = pick(
                    categoriesById[categoryId], ['term_id', 'name']
                );
            }
        }
        else {
            valueById[categoryId] = null;
        }
        return unMapObjects(valueById);

    }

    const render = (elements) => {

        return elements.map( (element) => {

            let [el, children] = element;
            let hasChildren = children.length !== 0;

            return (
                <div
                    className="editor-post-taxonomies__hierarchical-terms-choice"
                >
                    <CheckboxControl
                        checked={ valueById[el.term_id] !== undefined }
						onChange={ (checked) => {
                            onChange(updateCategories(el.term_id, checked));
                        } }
                        label={ unescapeString( el.name ) }
					/>
					{
                        hasChildren && (
                            <div className="editor-post-taxonomies__hierarchical-terms-subchoices">
                                { render( children ) }
                            </div>
                        )
                    }
                </div>
            );

        });

    }

    return (
        <BaseControl
            className={ (className ? className + ' ' : '') + 'categories' }
            label={ label }
        >
            <div className="editor-post-taxonomies__hierarchical-terms-list"
            >
                {
                    render(buildTree(categories))
                }
            </div>
        </BaseControl>
    );

}
