export default {
    root: {
        class: [
            // Flexbox
            'flex flex-col',
        ]
    },
    contentContainer: {
        class: [
            // Flexbox & Overflow
            'flex flex-col overflow-auto'
        ]
    },
    content: ({ props }) => ({
        class: [
            // Flexbox
            'flex',

            // Orientation
            {
                'flex-row': props.orientation !== 'vertical',
                'flex-col': props.orientation == 'vertical'
            },
            '[&>[data-pc-extend=button]]:self-center px-1'
        ]
    }),
    viewport: {
        class: [
            // Overflow & Width
            'overflow-hidden w-full'
        ]
    },
    itemList: ({ props }) => ({
        class: [
            // Flexbox
            'flex h-[170px] md:h-[225px]',

            // Orientation & Sizing
            {
                'flex-row': props.orientation !== 'vertical',
                'flex-col h-full': props.orientation == 'vertical'
            }
        ]
    }),
    item: ({ props }) => ({
        class: [
            // Flexbox
            'flex px-1',
            'shrink-0 grow-0', 

            // Size
            {
                // 'w-full sm:w-[50%] md:w-[33.333333333333336%]': props.orientation !== 'vertical',
                'w-full md:w-[70%] xl:w-[45%]': props.orientation !== 'vertical',

                'w-full h-full': props.orientation == 'vertical'
            }
        ]
    }),
    itemClone: ({ props }) => ({
        class: [
            // Flexbox
            'flex px-1',
            'shrink-0 grow-0', 
            'unvisible',    

            // Size
            {
                // 'w-full sm:w-[50%] md:w-[33.333333333333336%]': props.orientation !== 'vertical',
                'w-full md:w-[70%] xl:w-[45%]': props.orientation !== 'vertical',

                'w-full h-full': props.orientation == 'vertical'
            }
        ]
    }),
    indicatorList: {
        class: [
            // Flexbox & Alignment
            'flex flex-row justify-center flex-wrap'
        ]
    },
    indicator: {
        class: [
            // Spacing
            'mx-1'
        ]
    },
    indicatorButton: ({ context }) => ({
        class: [
            // Sizing & Shape
            'w-8 h-2 rounded-md',

            // Transitions
            'transition duration-200',

            // Focus Styles
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500',

            // Color & Background
            {
                'bg-surface-200 hover:bg-surface-300': !context.highlighted,
                'bg-primary hover:bg-primary-emphasis': context.highlighted
            }
        ]
    })
};
